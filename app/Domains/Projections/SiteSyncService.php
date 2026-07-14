<?php

namespace App\Domains\Projections;

use App\Domains\Projections\Builders\CategoryProjectionBuilder;
use App\Domains\Projections\Builders\ProductProjectionBuilder;
use App\Domains\Projections\Builders\SearchDocumentBuilder;
use App\Domains\Projections\Builders\SitemapBuilder;
use App\Domains\Projections\DTO\CategoryProjectionData;
use App\Domains\Projections\DTO\ProductProjectionData;
use App\Domains\Projections\DTO\SearchDocumentData;
use App\Domains\Projections\DTO\SitemapUrlData;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ProjectionJob;
use App\Models\ProjectionLog;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use App\Models\SiteSitemapUrl;
use Closure;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SiteSyncService
{
    private const SYNC_CHUNK_SIZE = 100;

    public function __construct(
        private readonly ProductProjectionBuilder $productProjectionBuilder,
        private readonly CategoryProjectionBuilder $categoryProjectionBuilder,
        private readonly SearchDocumentBuilder $searchDocumentBuilder,
        private readonly SitemapBuilder $sitemapBuilder,
    ) {}

    public function syncProduct(
        Site $site,
        CentralProduct $product,
        ?string $locale = null,
    ): SiteProductProjection {
        $locale = $this->locale($site, $locale);
        $job = $this->startJob($site, 'product', 'product', (int) $product->getKey(), $locale);

        try {
            $projection = $this->productProjectionBuilder->build($site, $product, $locale);
            $searchDocument = $this->searchDocumentBuilder->fromProductProjection($projection);
            $sitemapUrl = $this->sitemapBuilder->fromProductProjection($site, $projection);
            $record = $this->persistAtomically(
                $projection->siteId,
                fn (): SiteProductProjection => $this->persistProductBundle(
                    $product,
                    $projection,
                    $searchDocument,
                    $sitemapUrl,
                ),
            );

            $this->completeJob($job, 'product', (int) $product->getKey());

            return $record;
        } catch (Throwable $exception) {
            $this->failJob($job, 'product', (int) $product->getKey(), $exception);
            $this->markExistingProjectionFailed('product', $site, (int) $product->getKey(), $locale, $exception);

            throw $exception;
        }
    }

    public function syncCategory(
        Site $site,
        CentralCategory $category,
        ?string $locale = null,
    ): SiteCategoryProjection {
        $locale = $this->locale($site, $locale);
        $job = $this->startJob($site, 'category', 'category', (int) $category->getKey(), $locale);

        try {
            $projection = $this->categoryProjectionBuilder->build($site, $category, $locale);
            $searchDocument = $this->searchDocumentBuilder->fromCategoryProjection($projection);
            $sitemapUrl = $this->sitemapBuilder->fromCategoryProjection($site, $projection);
            $record = $this->persistAtomically(
                $projection->siteId,
                fn (): SiteCategoryProjection => $this->persistCategoryBundle(
                    $category,
                    $projection,
                    $searchDocument,
                    $sitemapUrl,
                ),
            );

            $this->completeJob($job, 'category', (int) $category->getKey());

            return $record;
        } catch (Throwable $exception) {
            $this->failJob($job, 'category', (int) $category->getKey(), $exception);
            $this->markExistingProjectionFailed('category', $site, (int) $category->getKey(), $locale, $exception);

            throw $exception;
        }
    }

    /**
     * @return array{
     *     categories: int,
     *     products: int,
     *     locales: int,
     *     failures: list<array{locale: string, entity_type: string, entity_id: int, message: string}>
     * }
     */
    public function syncSite(
        Site $site,
        ?string $locale = null,
        bool $productsOnly = false,
        bool $categoriesOnly = false,
    ): array {
        if ($productsOnly && $categoriesOnly) {
            throw new \InvalidArgumentException('Products-only and categories-only cannot be used together.');
        }

        $job = $this->startJob($site, 'site', 'site', (int) $site->getKey(), $locale);
        $locales = $locale === null || $locale === ''
            ? DB::table('site_locales')
                ->where('site_id', $site->getKey())
                ->where('is_enabled', true)
                ->orderBy('position')
                ->orderBy('id')
                ->pluck('locale_code')
                ->map(fn (mixed $locale): string => (string) $locale)
                ->all()
            : [$this->locale($site, $locale)];

        if ($locales === []) {
            $locales = [$this->locale($site, null)];
        }

        $counts = [
            'categories' => 0,
            'products' => 0,
            'locales' => count($locales),
            'failures' => [],
        ];

        try {
            foreach ($locales as $locale) {
                if (! $productsOnly) {
                    CentralCategory::query()
                        ->whereIn('id', DB::table('site_categories')
                            ->select('central_category_id')
                            ->where('site_id', $site->getKey())
                            ->where('is_enabled', true))
                        ->chunkById(self::SYNC_CHUNK_SIZE, function ($categories) use (
                            $site,
                            $locale,
                            $job,
                            &$counts,
                        ): void {
                            foreach ($categories as $category) {
                                try {
                                    $this->syncCategory($site, $category, $locale);
                                    $counts['categories']++;
                                } catch (Throwable $exception) {
                                    $this->recordItemFailure(
                                        $job,
                                        $counts,
                                        $locale,
                                        'category',
                                        (int) $category->getKey(),
                                        $exception,
                                    );
                                }
                            }
                        });
                }

                if (! $categoriesOnly) {
                    CentralProduct::query()
                        ->whereIn('id', DB::table('site_products')
                            ->select('central_product_id')
                            ->where('site_id', $site->getKey())
                            ->where('visibility', 'visible'))
                        ->chunkById(self::SYNC_CHUNK_SIZE, function ($products) use (
                            $site,
                            $locale,
                            $job,
                            &$counts,
                        ): void {
                            foreach ($products as $product) {
                                try {
                                    $this->syncProduct($site, $product, $locale);
                                    $counts['products']++;
                                } catch (Throwable $exception) {
                                    $this->recordItemFailure(
                                        $job,
                                        $counts,
                                        $locale,
                                        'product',
                                        (int) $product->getKey(),
                                        $exception,
                                    );
                                }
                            }
                        });
                }
            }

            $job->forceFill(['payload_json' => $counts])->save();
            $this->completeJob($job, 'site', (int) $site->getKey(), $counts);

            return $counts;
        } catch (Throwable $exception) {
            $this->failJob($job, 'site', (int) $site->getKey(), $exception, $counts);

            throw $exception;
        }
    }

    private function persistProductBundle(
        CentralProduct $product,
        ProductProjectionData $projection,
        SearchDocumentData $searchDocument,
        SitemapUrlData $sitemapUrl,
    ): SiteProductProjection {
        $builtAt = now();
        $record = SiteProductProjection::query()->updateOrCreate([
            'site_id' => $projection->siteId,
            'locale' => $projection->locale,
            'central_product_id' => $projection->centralProductId,
        ], [
            'central_product_version' => $this->sourceVersion($product),
            'slug' => $projection->slug,
            'canonical_url' => $projection->seo['canonical_url'] ?? null,
            'title' => $projection->title,
            'status' => $projection->status,
            'payload_json' => $projection->payload,
            'seo_json' => $projection->seo,
            'media_json' => $projection->media,
            'search_summary_json' => [
                'search_text' => $searchDocument->searchText,
                'filter_values' => $searchDocument->filterValues,
                'sort_values' => $searchDocument->sortValues,
            ],
            'checksum' => $projection->checksum,
            'built_at' => $builtAt,
            'stale_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
        ]);

        $this->persistSearchDocument($searchDocument, $builtAt);
        $this->persistSitemapUrl($sitemapUrl);

        return $record;
    }

    private function persistCategoryBundle(
        CentralCategory $category,
        CategoryProjectionData $projection,
        SearchDocumentData $searchDocument,
        SitemapUrlData $sitemapUrl,
    ): SiteCategoryProjection {
        $builtAt = now();
        $record = SiteCategoryProjection::query()->updateOrCreate([
            'site_id' => $projection->siteId,
            'locale' => $projection->locale,
            'central_category_id' => $projection->centralCategoryId,
        ], [
            'central_category_version' => $this->sourceVersion($category),
            'parent_category_id' => $projection->parentCategoryId,
            'slug' => $projection->slug,
            'title' => $projection->title,
            'status' => $projection->status,
            'payload_json' => $projection->payload,
            'seo_json' => $projection->seo,
            'facets_json' => $projection->facets,
            'comparison_json' => $projection->comparison,
            'checksum' => $projection->checksum,
            'built_at' => $builtAt,
            'stale_at' => null,
            'failed_at' => null,
            'failure_reason' => null,
        ]);

        $this->persistSearchDocument($searchDocument, $builtAt);
        $this->persistSitemapUrl($sitemapUrl);

        return $record;
    }

    private function persistSearchDocument(SearchDocumentData $document, mixed $builtAt): void
    {
        SiteSearchDocument::query()->updateOrCreate([
            'site_id' => $document->siteId,
            'locale' => $document->locale,
            'document_type' => $document->documentType,
            'document_id' => $document->documentId,
        ], [
            'title' => $document->title,
            'slug' => $document->slug,
            'status' => $document->status,
            'search_text' => $document->searchText,
            'filter_values_json' => $document->filterValues,
            'sort_values_json' => $document->sortValues,
            'payload_json' => $document->payload,
            'checksum' => $document->checksum,
            'built_at' => $builtAt,
            'stale_at' => null,
        ]);
    }

    private function persistSitemapUrl(SitemapUrlData $sitemapUrl): void
    {
        SiteSitemapUrl::query()
            ->where('site_id', $sitemapUrl->siteId)
            ->where('locale', $sitemapUrl->locale)
            ->where('entity_type', $sitemapUrl->entityType)
            ->where('entity_id', $sitemapUrl->entityId)
            ->where('url', '!=', $sitemapUrl->url)
            ->delete();

        SiteSitemapUrl::query()->updateOrCreate([
            'site_id' => $sitemapUrl->siteId,
            'locale' => $sitemapUrl->locale,
            'url' => $sitemapUrl->url,
        ], [
            'entity_type' => $sitemapUrl->entityType,
            'entity_id' => $sitemapUrl->entityId,
            'changefreq' => $sitemapUrl->changefreq,
            'priority' => $sitemapUrl->priority,
            'lastmod_at' => $sitemapUrl->lastmodAt,
            'status' => $sitemapUrl->status,
            'checksum' => $sitemapUrl->checksum,
        ]);
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function persistAtomically(int $siteId, Closure $callback): mixed
    {
        return DB::transaction(function () use ($siteId, $callback): mixed {
            Site::query()->whereKey($siteId)->lockForUpdate()->firstOrFail();

            return $callback();
        }, 3);
    }

    private function startJob(
        Site $site,
        string $jobType,
        string $entityType,
        int $entityId,
        ?string $locale,
    ): ProjectionJob {
        $job = ProjectionJob::query()->create([
            'site_id' => $site->getKey(),
            'job_type' => $jobType,
            'status' => 'building',
            'target_type' => $entityType,
            'target_id' => $entityId,
            'locale' => $locale,
            'attempts' => 1,
            'started_at' => now(),
        ]);

        $this->log($job, 'info', 'started', ucfirst($jobType).' projection sync started.', $entityType, $entityId);

        return $job;
    }

    /** @param array<string, mixed> $context */
    private function completeJob(
        ProjectionJob $job,
        string $entityType,
        int $entityId,
        array $context = [],
    ): void {
        $job->forceFill([
            'status' => 'completed',
            'finished_at' => now(),
            'failed_at' => null,
            'failure_reason' => null,
        ])->save();
        $this->log($job, 'info', 'completed', ucfirst($job->job_type).' projection sync completed.', $entityType, $entityId, $context);
    }

    /** @param array<string, mixed> $context */
    private function failJob(
        ProjectionJob $job,
        string $entityType,
        int $entityId,
        Throwable $exception,
        array $context = [],
    ): void {
        $job->forceFill([
            'status' => 'failed',
            'failed_at' => now(),
            'finished_at' => now(),
            'failure_reason' => $exception->getMessage(),
        ])->save();
        $this->log(
            $job,
            'error',
            'failed',
            $exception->getMessage(),
            $entityType,
            $entityId,
            [...$context, 'exception' => $exception::class],
        );
    }

    /** @param array<string, mixed> $context */
    private function log(
        ProjectionJob $job,
        string $level,
        string $event,
        string $message,
        string $entityType,
        int $entityId,
        array $context = [],
    ): void {
        ProjectionLog::query()->create([
            'projection_job_id' => $job->getKey(),
            'site_id' => $job->site_id,
            'level' => $level,
            'event' => $event,
            'message' => $message,
            'context_json' => $context,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }

    /**
     * @param  array{
     *     categories: int,
     *     products: int,
     *     locales: int,
     *     failures: list<array{locale: string, entity_type: string, entity_id: int, message: string}>
     * }  $counts
     */
    private function recordItemFailure(
        ProjectionJob $job,
        array &$counts,
        string $locale,
        string $entityType,
        int $entityId,
        Throwable $exception,
    ): void {
        $failure = [
            'locale' => $locale,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'message' => $exception->getMessage(),
        ];
        $counts['failures'][] = $failure;
        $this->log(
            $job,
            'warning',
            'item_failed',
            ucfirst($entityType).' projection sync failed; site sync continued.',
            $entityType,
            $entityId,
            $failure,
        );
    }

    private function markExistingProjectionFailed(
        string $entityType,
        Site $site,
        int $entityId,
        string $locale,
        Throwable $exception,
    ): void {
        $model = $entityType === 'product'
            ? SiteProductProjection::query()->where('central_product_id', $entityId)
            : SiteCategoryProjection::query()->where('central_category_id', $entityId);

        $model
            ->where('site_id', $site->getKey())
            ->where('locale', $locale)
            ->update([
                'status' => 'failed',
                'failed_at' => now(),
                'failure_reason' => $exception->getMessage(),
            ]);
    }

    private function locale(Site $site, ?string $locale): string
    {
        return filled($locale) ? (string) $locale : (string) $site->getAttribute('default_locale');
    }

    private function sourceVersion(Model $model): ?int
    {
        $updatedAt = $model->getAttribute('updated_at');

        return $updatedAt instanceof DateTimeInterface ? (int) $updatedAt->format('U') : null;
    }
}

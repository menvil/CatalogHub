<?php

namespace App\Domains\Projections;

use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ProjectionLog;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use Illuminate\Support\Facades\DB;
use LogicException;

final class ProjectionStaleDetector
{
    private const DETECTION_CHUNK_SIZE = 500;

    public function markStaleForProduct(
        CentralProduct $product,
        ?string $reason = null,
    ): int {
        return $this->markProductIdStale(
            (int) $product->getKey(),
            null,
            $reason ?? 'central_product_updated',
        );
    }

    public function markStaleForCategory(
        CentralCategory $category,
        ?string $reason = null,
    ): int {
        return $this->markCategoryIdStale(
            (int) $category->getKey(),
            null,
            $reason ?? 'category_schema_updated',
        );
    }

    /** @return array{products: int, categories: int, search_documents: int} */
    public function markStaleForSite(Site $site, ?string $reason = null): array
    {
        $staleAt = now();
        $reason ??= 'site_updated';

        return DB::transaction(function () use ($site, $staleAt, $reason): array {
            $products = SiteProductProjection::query()
                ->where('site_id', $site->getKey())
                ->update(['status' => 'stale', 'stale_at' => $staleAt]);
            $categories = SiteCategoryProjection::query()
                ->where('site_id', $site->getKey())
                ->update(['status' => 'stale', 'stale_at' => $staleAt]);
            $searchDocuments = SiteSearchDocument::query()
                ->where('site_id', $site->getKey())
                ->update(['status' => 'stale', 'stale_at' => $staleAt]);

            $this->logStale((int) $site->getKey(), 'site', (int) $site->getKey(), $reason, [
                'products' => $products,
                'categories' => $categories,
                'search_documents' => $searchDocuments,
            ]);

            return [
                'products' => $products,
                'categories' => $categories,
                'search_documents' => $searchDocuments,
            ];
        });
    }

    /** @return array{products: int, categories: int} */
    public function detectStaleForSite(Site $site): array
    {
        $counts = ['products' => 0, 'categories' => 0];
        $siteId = (int) $site->getKey();
        $productVersion = $this->sourceVersionExpression('central_products.updated_at');
        $categoryVersion = $this->sourceVersionExpression('central_categories.updated_at');

        SiteProductProjection::query()
            ->join(
                'central_products',
                'central_products.id',
                '=',
                'site_product_projections.central_product_id',
            )
            ->where('site_product_projections.site_id', $site->getKey())
            ->whereRaw($this->versionMismatchSql(
                'site_product_projections.central_product_version',
                'central_products.updated_at',
                $productVersion,
            ))
            ->select([
                'site_product_projections.id as projection_id',
                'site_product_projections.central_product_id',
            ])
            ->chunkById(
                self::DETECTION_CHUNK_SIZE,
                function ($projections) use ($siteId, &$counts): void {
                    $projectionIds = $projections->pluck('projection_id')
                        ->map(fn (mixed $id): int => (int) $id)
                        ->all();
                    $productIds = $projections->pluck('central_product_id')
                        ->map(fn (mixed $id): int => (int) $id)
                        ->unique()
                        ->values()
                        ->all();
                    $counts['products'] += $this->markProductProjectionBatchStale(
                        $siteId,
                        $projectionIds,
                        $productIds,
                    );
                },
                'site_product_projections.id',
                'projection_id',
            );

        SiteCategoryProjection::query()
            ->join(
                'central_categories',
                'central_categories.id',
                '=',
                'site_category_projections.central_category_id',
            )
            ->where('site_category_projections.site_id', $site->getKey())
            ->whereRaw($this->versionMismatchSql(
                'site_category_projections.central_category_version',
                'central_categories.updated_at',
                $categoryVersion,
            ))
            ->select([
                'site_category_projections.id as projection_id',
                'site_category_projections.central_category_id',
            ])
            ->chunkById(
                self::DETECTION_CHUNK_SIZE,
                function ($projections) use ($siteId, &$counts): void {
                    $projectionIds = $projections->pluck('projection_id')
                        ->map(fn (mixed $id): int => (int) $id)
                        ->all();
                    $categoryIds = $projections->pluck('central_category_id')
                        ->map(fn (mixed $id): int => (int) $id)
                        ->unique()
                        ->values()
                        ->all();
                    $counts['categories'] += $this->markCategoryProjectionBatchStale(
                        $siteId,
                        $projectionIds,
                        $categoryIds,
                    );
                },
                'site_category_projections.id',
                'projection_id',
            );

        return $counts;
    }

    /**
     * @param  list<int>  $projectionIds
     * @param  list<int>  $productIds
     */
    private function markProductProjectionBatchStale(
        int $siteId,
        array $projectionIds,
        array $productIds,
    ): int {
        return DB::transaction(function () use ($siteId, $projectionIds, $productIds): int {
            $staleAt = now();
            $count = SiteProductProjection::query()
                ->whereIn('id', $projectionIds)
                ->update(['status' => 'stale', 'stale_at' => $staleAt]);
            SiteSearchDocument::query()
                ->where('site_id', $siteId)
                ->where('document_type', 'product')
                ->whereIn('document_id', $productIds)
                ->update(['status' => 'stale', 'stale_at' => $staleAt]);
            $this->insertStaleLogs($siteId, 'product', $productIds, 'central_product_updated', $staleAt);

            return $count;
        });
    }

    /**
     * @param  list<int>  $projectionIds
     * @param  list<int>  $categoryIds
     */
    private function markCategoryProjectionBatchStale(
        int $siteId,
        array $projectionIds,
        array $categoryIds,
    ): int {
        return DB::transaction(function () use ($siteId, $projectionIds, $categoryIds): int {
            $staleAt = now();
            $count = SiteCategoryProjection::query()
                ->whereIn('id', $projectionIds)
                ->update(['status' => 'stale', 'stale_at' => $staleAt]);
            SiteSearchDocument::query()
                ->where('site_id', $siteId)
                ->where('document_type', 'category')
                ->whereIn('document_id', $categoryIds)
                ->update(['status' => 'stale', 'stale_at' => $staleAt]);
            $this->insertStaleLogs($siteId, 'category', $categoryIds, 'category_schema_updated', $staleAt);

            return $count;
        });
    }

    /** @param list<int> $entityIds */
    private function insertStaleLogs(
        int $siteId,
        string $entityType,
        array $entityIds,
        string $reason,
        mixed $createdAt,
    ): void {
        ProjectionLog::query()->insert(array_map(
            fn (int $entityId): array => [
                'site_id' => $siteId,
                'level' => 'warning',
                'event' => 'stale',
                'message' => $reason,
                'context_json' => json_encode(['reason' => $reason], JSON_THROW_ON_ERROR),
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'created_at' => $createdAt,
            ],
            $entityIds,
        ));
    }

    private function sourceVersionExpression(string $updatedAtColumn): string
    {
        return match (DB::getDriverName()) {
            'pgsql' => "CAST(EXTRACT(EPOCH FROM {$updatedAtColumn}) AS BIGINT)",
            'mysql', 'mariadb' => "UNIX_TIMESTAMP({$updatedAtColumn})",
            'sqlite' => "CAST(strftime('%s', {$updatedAtColumn}) AS INTEGER)",
            'sqlsrv' => "DATEDIFF_BIG(second, '1970-01-01', {$updatedAtColumn})",
            default => throw new LogicException('Unsupported database driver for projection stale detection.'),
        };
    }

    private function versionMismatchSql(
        string $projectionVersionColumn,
        string $sourceUpdatedAtColumn,
        string $sourceVersionExpression,
    ): string {
        return "(({$projectionVersionColumn} IS NULL AND {$sourceUpdatedAtColumn} IS NOT NULL)"
            ." OR ({$projectionVersionColumn} IS NOT NULL AND {$sourceUpdatedAtColumn} IS NULL)"
            ." OR ({$projectionVersionColumn} IS NOT NULL AND {$sourceUpdatedAtColumn} IS NOT NULL"
            ." AND {$projectionVersionColumn} <> {$sourceVersionExpression}))";
    }

    private function markProductIdStale(int $productId, ?int $siteId, string $reason): int
    {
        $query = SiteProductProjection::query()->where('central_product_id', $productId);

        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        $siteIds = (clone $query)->pluck('site_id')->unique()->map(fn (mixed $id): int => (int) $id);

        if ($siteIds->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($query, $siteIds, $productId, $reason): int {
            $staleAt = now();
            $count = $query->update(['status' => 'stale', 'stale_at' => $staleAt]);
            SiteSearchDocument::query()
                ->whereIn('site_id', $siteIds)
                ->where('document_type', 'product')
                ->where('document_id', $productId)
                ->update(['status' => 'stale', 'stale_at' => $staleAt]);

            foreach ($siteIds as $siteId) {
                $this->logStale($siteId, 'product', $productId, $reason);
            }

            return $count;
        });
    }

    private function markCategoryIdStale(int $categoryId, ?int $siteId, string $reason): int
    {
        $query = SiteCategoryProjection::query()->where('central_category_id', $categoryId);

        if ($siteId !== null) {
            $query->where('site_id', $siteId);
        }

        $siteIds = (clone $query)->pluck('site_id')->unique()->map(fn (mixed $id): int => (int) $id);

        if ($siteIds->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($query, $siteIds, $categoryId, $reason): int {
            $staleAt = now();
            $count = $query->update(['status' => 'stale', 'stale_at' => $staleAt]);
            SiteSearchDocument::query()
                ->whereIn('site_id', $siteIds)
                ->where('document_type', 'category')
                ->where('document_id', $categoryId)
                ->update(['status' => 'stale', 'stale_at' => $staleAt]);

            foreach ($siteIds as $siteId) {
                $this->logStale($siteId, 'category', $categoryId, $reason);
            }

            return $count;
        });
    }

    /** @param array<string, int> $context */
    private function logStale(
        int $siteId,
        string $entityType,
        int $entityId,
        string $reason,
        array $context = [],
    ): void {
        ProjectionLog::query()->create([
            'site_id' => $siteId,
            'level' => 'warning',
            'event' => 'stale',
            'message' => $reason,
            'context_json' => ['reason' => $reason, ...$context],
            'entity_type' => $entityType,
            'entity_id' => $entityId,
        ]);
    }
}

<?php

namespace App\Domains\Projections;

use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ProjectionLog;
use App\Models\Site;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class ProjectionStaleDetector
{
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
        $productIds = SiteProductProjection::query()
            ->with('product')
            ->where('site_id', $site->getKey())
            ->get()
            ->filter(function (SiteProductProjection $projection): bool {
                $product = $projection->product;

                return $product instanceof CentralProduct
                    && $projection->central_product_version !== $this->sourceVersion($product);
            })
            ->pluck('central_product_id')
            ->unique();
        $categoryIds = SiteCategoryProjection::query()
            ->with('category')
            ->where('site_id', $site->getKey())
            ->get()
            ->filter(function (SiteCategoryProjection $projection): bool {
                $category = $projection->category;

                return $category instanceof CentralCategory
                    && $projection->central_category_version !== $this->sourceVersion($category);
            })
            ->pluck('central_category_id')
            ->unique();

        $counts = ['products' => 0, 'categories' => 0];

        foreach ($productIds as $productId) {
            $counts['products'] += $this->markProductIdStale(
                (int) $productId,
                (int) $site->getKey(),
                'central_product_updated',
            );
        }

        foreach ($categoryIds as $categoryId) {
            $counts['categories'] += $this->markCategoryIdStale(
                (int) $categoryId,
                (int) $site->getKey(),
                'category_schema_updated',
            );
        }

        return $counts;
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

    private function sourceVersion(Model $model): ?int
    {
        $updatedAt = $model->getAttribute('updated_at');

        return $updatedAt instanceof DateTimeInterface ? (int) $updatedAt->format('U') : null;
    }
}

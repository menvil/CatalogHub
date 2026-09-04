<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use App\Data\CentralCatalog\CentralBrandCategoryCoverage;
use App\Enums\CentralCategoryStatus;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Support\Collection;

final class CentralBrandCategoryCoverageQuery implements RawSqlPersistenceBoundary
{
    /** @return Collection<int, CentralBrandCategoryCoverage> */
    public function forBrand(CentralBrand $brand): Collection
    {
        return CentralProduct::query()
            ->join('central_categories', 'central_categories.id', '=', 'central_products.central_category_id')
            ->where('central_products.central_brand_id', $brand->getKey())
            ->where('central_products.status', '!=', CentralProductStatus::Archived->value)
            ->select([
                'central_categories.id as category_id',
                'central_categories.name as category_name',
                'central_categories.status as category_status',
            ])
            ->selectRaw('COUNT(central_products.id) AS products_count')
            ->groupBy([
                'central_categories.id',
                'central_categories.name',
                'central_categories.status',
            ])
            ->orderByDesc('products_count')
            ->orderBy('central_categories.name')
            ->orderBy('central_categories.id')
            ->get()
            ->map(static fn (CentralProduct $row): CentralBrandCategoryCoverage => new CentralBrandCategoryCoverage(
                categoryId: (int) $row->getAttribute('category_id'),
                name: (string) $row->getAttribute('category_name'),
                status: CentralCategoryStatus::from((string) $row->getAttribute('category_status')),
                productsCount: (int) $row->getAttribute('products_count'),
            ));
    }

    /**
     * @param  Collection<int, CentralBrand>  $brands
     * @return Collection<int<0, max>, int>
     */
    public function countsForBrands(Collection $brands): Collection
    {
        $brandIds = $brands
            ->map(static fn (CentralBrand $brand): int => (int) $brand->getKey())
            ->values()
            ->all();

        if ($brandIds === []) {
            return collect();
        }

        return CentralProduct::query()
            ->whereIn('central_brand_id', $brandIds)
            ->where('status', '!=', CentralProductStatus::Archived->value)
            ->whereNotNull('central_category_id')
            ->select('central_brand_id')
            ->selectRaw('COUNT(DISTINCT central_category_id) AS categories_count')
            ->groupBy('central_brand_id')
            ->get()
            ->mapWithKeys(static fn (CentralProduct $row): array => [
                (int) $row->central_brand_id => (int) $row->getAttribute('categories_count'),
            ]);
    }
}

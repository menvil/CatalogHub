<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Support\Collection;

final class CentralBrandDetailQuery
{
    public function loadUsage(CentralBrand $brand): CentralBrand
    {
        return $brand
            ->load(['country.translations', 'tags', 'ownership.organization'])
            ->loadCount([
                'products' => static fn ($query) => $query
                    ->where('status', '!=', CentralProductStatus::Archived->value),
            ]);
    }

    /** @return Collection<int, CentralProduct> */
    public function recentProducts(CentralBrand $brand, int $limit = 5): Collection
    {
        return CentralProduct::query()
            ->with('category')
            ->where('central_brand_id', $brand->getKey())
            ->where('status', '!=', CentralProductStatus::Archived->value)
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}

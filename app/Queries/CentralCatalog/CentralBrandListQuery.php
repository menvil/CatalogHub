<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Contracts\Persistence\StablePaginationBoundary;
use App\Data\CentralCatalog\BrandListFiltersData;
use App\Models\CentralCatalog\CentralBrand;
use App\Support\Normalization\BrandInputNormalizer;
use Illuminate\Pagination\LengthAwarePaginator;

final class CentralBrandListQuery implements StablePaginationBoundary
{
    /** @return LengthAwarePaginator<int, CentralBrand> */
    public function paginate(BrandListFiltersData $filters, ?int $page = null): LengthAwarePaginator
    {
        $sortColumn = $filters->sort === 'name' ? 'normalized_name' : $filters->sort;

        return CentralBrand::query()
            ->when($filters->search !== null, function ($query) use ($filters): void {
                $normalizedSearch = BrandInputNormalizer::nameIdentity($filters->search);
                $slugSearch = mb_strtolower($filters->search, 'UTF-8');

                $query->where(function ($query) use ($normalizedSearch, $slugSearch): void {
                    $query->where('normalized_name', 'like', "%{$normalizedSearch}%")
                        ->orWhere('slug', 'like', "%{$slugSearch}%");
                });
            })
            ->when($filters->status !== null, fn ($query) => $query->where('status', $filters->status))
            ->orderBy($sortColumn, $filters->direction)
            ->orderBy('id')
            ->paginate($filters->perPage, ['*'], 'page', $page)
            ->withQueryString();
    }
}

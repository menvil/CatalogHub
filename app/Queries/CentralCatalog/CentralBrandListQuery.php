<?php

namespace App\Queries\CentralCatalog;

use App\Contracts\Persistence\StablePaginationBoundary;
use App\Data\CentralCatalog\CentralBrandListFiltersData;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Pagination\LengthAwarePaginator;

final class CentralBrandListQuery implements StablePaginationBoundary
{
    /** @return LengthAwarePaginator<int, CentralBrand> */
    public function paginate(
        CentralBrandListFiltersData $filters,
        int $perPage = 20,
        ?int $page = null,
    ): LengthAwarePaginator {
        $sort = $filters->sort === 'status' ? 'status' : 'name';
        $direction = $filters->direction === 'desc' ? 'desc' : 'asc';

        return CentralBrand::query()
            ->when($filters->search !== null, function ($query) use ($filters): void {
                $query->where(function ($query) use ($filters): void {
                    $query->where('name', 'like', "%{$filters->search}%")
                        ->orWhere('slug', 'like', "%{$filters->search}%");
                });
            })
            ->when($filters->status !== null, fn ($query) => $query->where('status', $filters->status))
            ->orderBy($sort, $direction)
            ->when($sort === 'status', fn ($query) => $query->orderBy('name'))
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }
}

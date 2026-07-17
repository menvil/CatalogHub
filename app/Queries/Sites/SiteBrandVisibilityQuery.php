<?php

namespace App\Queries\Sites;

use App\Contracts\Persistence\StablePaginationBoundary;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Site;
use Illuminate\Pagination\LengthAwarePaginator;

final class SiteBrandVisibilityQuery implements StablePaginationBoundary
{
    /** @return LengthAwarePaginator<int, CentralBrand> */
    public function paginate(
        string $search = '',
        int $perPage = 50,
        ?int $page = null,
    ): LengthAwarePaginator {
        return CentralBrand::query()
            ->when($search !== '', fn ($query) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function findBrand(int $brandId): CentralBrand
    {
        return CentralBrand::query()->findOrFail($brandId);
    }

    public function refreshSite(Site $site): Site
    {
        return Site::query()->findOrFail($site->getKey());
    }
}

<?php

namespace App\Queries\Sites;

use App\Contracts\Persistence\StablePaginationBoundary;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteProduct;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

final class SiteProductManagementQuery implements StablePaginationBoundary
{
    /** @return LengthAwarePaginator<int, CentralProduct> */
    public function paginate(
        Site $site,
        string $search = '',
        int $perPage = 50,
        ?int $page = null,
    ): LengthAwarePaginator {
        $categoryIds = SiteCategory::query()
            ->select('central_category_id')
            ->where('site_id', $site->id)
            ->enabled();

        return CentralProduct::query()
            ->whereIn('central_category_id', $categoryIds)
            ->where('status', CentralProductStatus::Active)
            ->when($search !== '', fn ($query) => $query->whereLike('name', '%'.$search.'%'))
            ->with('brand')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    /** @param list<int> $productIds
     * @return Collection<int, SiteProduct>
     */
    public function states(Site $site, array $productIds): Collection
    {
        return SiteProduct::query()
            ->where('site_id', $site->id)
            ->whereIn('central_product_id', $productIds)
            ->get();
    }

    public function findProduct(int $productId): CentralProduct
    {
        return CentralProduct::query()->findOrFail($productId);
    }
}

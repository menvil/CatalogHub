<?php

namespace App\Services\Sync;

use App\Models\Site;
use App\Models\SiteProduct;
use Illuminate\Database\Eloquent\Builder;

final class StaleProductDetector
{
    public function isStale(SiteProduct $siteProduct): bool
    {
        $siteProduct->loadMissing('centralProduct');

        return $siteProduct->sync_status === 'failed'
            || $siteProduct->centralProduct->version > $siteProduct->published_version;
    }

    /** @return Builder<SiteProduct> */
    public function staleForSite(Site $site): Builder
    {
        return SiteProduct::query()
            ->select('site_products.*')
            ->join('central_products', 'central_products.id', '=', 'site_products.central_product_id')
            ->where('site_products.site_id', $site->getKey())
            ->where(function (Builder $query): void {
                $query->whereColumn('central_products.version', '>', 'site_products.published_version')
                    ->orWhere('site_products.sync_status', 'failed');
            });
    }
}

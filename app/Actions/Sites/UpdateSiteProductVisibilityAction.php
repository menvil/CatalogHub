<?php

namespace App\Actions\Sites;

use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Services\Sites\SiteBrandVisibilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateSiteProductVisibilityAction
{
    public function handle(Site $site, CentralProduct $product, string $visibility, bool $featured = false): SiteProduct
    {
        if (! in_array($visibility, ['visible', 'hidden', 'excluded'], true)) {
            throw ValidationException::withMessages(['visibility' => 'Invalid site product visibility.']);
        }

        return DB::transaction(function () use ($featured, $product, $site, $visibility): SiteProduct {
            $lockedSite = Site::query()->whereKey($site->getKey())->lockForUpdate()->firstOrFail();

            if ($product->status !== CentralProductStatus::Active) {
                throw ValidationException::withMessages(['product' => 'Only active products can be managed for a site.']);
            }

            $categoryEnabled = DB::table('site_categories')->where('site_id', $lockedSite->id)->where('central_category_id', $product->central_category_id)->where('is_enabled', true)->exists();
            if (! $categoryEnabled) {
                throw ValidationException::withMessages(['product' => 'The product category is not enabled for this site.']);
            }

            if ($visibility === 'visible' && ! app(SiteBrandVisibilityService::class)->allowsProduct($lockedSite, $product)) {
                throw ValidationException::withMessages(['product' => 'The product brand is hidden for this site.']);
            }

            return $lockedSite->products()->updateOrCreate(['central_product_id' => $product->id], ['visibility' => $visibility, 'is_featured' => $featured]);
        });
    }
}

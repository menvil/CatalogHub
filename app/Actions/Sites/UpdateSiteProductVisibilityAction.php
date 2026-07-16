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
    public function handle(Site $site, CentralProduct $product, string $visibility, ?bool $featured = null): SiteProduct
    {
        $this->validateVisibility($visibility);

        return DB::transaction(function () use ($featured, $product, $site, $visibility): SiteProduct {
            $lockedSite = Site::query()->whereKey($site->getKey())->lockForUpdate()->firstOrFail();
            $existing = $lockedSite->products()->where('central_product_id', $product->getKey())->first();

            return $this->updateLocked(
                $lockedSite,
                $product,
                $visibility,
                $featured ?? ($existing instanceof SiteProduct && $existing->is_featured),
            );
        });
    }

    public function toggleFeatured(Site $site, CentralProduct $product): SiteProduct
    {
        return DB::transaction(function () use ($product, $site): SiteProduct {
            $lockedSite = Site::query()->whereKey($site->getKey())->lockForUpdate()->firstOrFail();
            $existing = $lockedSite->products()->where('central_product_id', $product->getKey())->first();
            $visibility = $existing instanceof SiteProduct ? $existing->visibility : 'hidden';
            $featured = $existing instanceof SiteProduct ? ! $existing->is_featured : true;

            return $this->updateLocked($lockedSite, $product, $visibility, $featured);
        });
    }

    private function updateLocked(Site $site, CentralProduct $product, string $visibility, bool $featured): SiteProduct
    {
        if ($product->status !== CentralProductStatus::Active) {
            throw ValidationException::withMessages(['product' => 'Only active products can be managed for a site.']);
        }

        $categoryEnabled = $site->categories()
            ->enabled()
            ->where('central_category_id', $product->central_category_id)
            ->exists();
        if (! $categoryEnabled) {
            throw ValidationException::withMessages(['product' => 'The product category is not enabled for this site.']);
        }

        if ($visibility === 'visible' && ! app(SiteBrandVisibilityService::class)->allowsProduct($site, $product)) {
            throw ValidationException::withMessages(['product' => 'The product brand is hidden for this site.']);
        }

        return $site->products()->updateOrCreate(['central_product_id' => $product->id], ['visibility' => $visibility, 'is_featured' => $featured]);
    }

    private function validateVisibility(string $visibility): void
    {
        if (! in_array($visibility, ['visible', 'hidden', 'excluded'], true)) {
            throw ValidationException::withMessages(['visibility' => 'Invalid site product visibility.']);
        }
    }
}

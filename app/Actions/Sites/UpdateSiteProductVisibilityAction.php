<?php

namespace App\Actions\Sites;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class UpdateSiteProductVisibilityAction
{
    public function handle(Site $site, CentralProduct $product, string $visibility, bool $featured = false): SiteProduct
    {
        if (! in_array($visibility, ['visible', 'hidden', 'excluded'], true)) {
            throw ValidationException::withMessages(['visibility' => 'Invalid site product visibility.']);
        }
        $categoryEnabled = DB::table('site_categories')->where('site_id', $site->id)->where('central_category_id', $product->central_category_id)->where('is_enabled', true)->exists();
        if (! $categoryEnabled) {
            throw ValidationException::withMessages(['product' => 'The product category is not enabled for this site.']);
        }

        return $site->products()->updateOrCreate(['central_product_id' => $product->id], ['visibility' => $visibility, 'is_featured' => $featured]);
    }
}

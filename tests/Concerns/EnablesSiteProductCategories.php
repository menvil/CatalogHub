<?php

namespace Tests\Concerns;

use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Illuminate\Support\Facades\DB;

trait EnablesSiteProductCategories
{
    protected function enableSiteCategory(Site $site, CentralCategory $category, bool $enabled = true): void
    {
        DB::table('site_categories')->insert([
            'site_id' => $site->id,
            'central_category_id' => $category->id,
            'is_enabled' => $enabled,
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    protected function enableProductCategory(Site $site, CentralProduct $product, bool $enabled = true): void
    {
        $categoryId = $product->getAttribute('central_category_id');

        if (! is_int($categoryId)) {
            $categoryId = CentralCategory::factory()->create()->id;
            $product->update(['central_category_id' => $categoryId]);
        }

        $this->enableSiteCategory($site, CentralCategory::query()->findOrFail($categoryId), $enabled);
    }
}

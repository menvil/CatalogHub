<?php

namespace Database\Factories;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SiteProduct> */
class SiteProductFactory extends Factory
{
    protected $model = SiteProduct::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'central_product_id' => CentralProduct::factory(),
            'visibility' => 'visible',
            'is_featured' => false,
            'position' => null,
            'published_version' => 0,
            'last_synced_at' => null,
            'sync_status' => null,
            'settings_json' => [],
        ];
    }
}

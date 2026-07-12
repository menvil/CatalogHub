<?php

namespace Database\Factories;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaAssignment>
 */
class MediaAssignmentFactory extends Factory
{
    protected $model = MediaAssignment::class;

    public function definition(): array
    {
        return [
            'media_asset_id' => MediaAsset::factory(),
            'entity_type' => 'central_product',
            'entity_id' => CentralProduct::factory(),
            'role' => fake()->randomElement(['main', 'card', 'gallery', 'hero', 'og']),
            'position' => 0,
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => false,
            'visibility' => 'global',
        ];
    }
}

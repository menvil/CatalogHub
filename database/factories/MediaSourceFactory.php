<?php

namespace Database\Factories;

use App\Models\MediaAsset;
use App\Models\MediaSource;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MediaSource>
 */
class MediaSourceFactory extends Factory
{
    protected $model = MediaSource::class;

    public function definition(): array
    {
        return [
            'media_asset_id' => MediaAsset::factory(),
            'source_type' => 'manual',
            'source_url' => fake()->url(),
            'source_name' => fake()->company(),
            'license_type' => 'manufacturer',
            'license_url' => fake()->url(),
            'attribution' => fake()->sentence(),
            'metadata' => ['note' => fake()->word()],
        ];
    }
}

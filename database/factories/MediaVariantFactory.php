<?php

namespace Database\Factories;

use App\Models\MediaAsset;
use App\Models\MediaVariant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MediaVariant>
 */
class MediaVariantFactory extends Factory
{
    protected $model = MediaVariant::class;

    public function definition(): array
    {
        $type = fake()->randomElement(['thumbnail', 'card', 'gallery', 'hero', 'og']);

        return [
            'media_asset_id' => MediaAsset::factory(),
            'variant_type' => $type,
            'disk' => 'public',
            'path' => 'media/variants/'.Str::uuid()."/{$type}.webp",
            'width' => 640,
            'height' => 640,
            'format' => 'webp',
            'file_size' => fake()->numberBetween(5_000, 300_000),
            'quality' => 84,
            'status' => 'ready',
        ];
    }
}

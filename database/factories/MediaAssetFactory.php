<?php

namespace Database\Factories;

use App\Models\MediaAsset;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MediaAsset>
 */
class MediaAssetFactory extends Factory
{
    protected $model = MediaAsset::class;

    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'uuid' => $uuid,
            'type' => 'image',
            'source' => 'manual',
            'disk' => 'public',
            'original_path' => "media/originals/{$uuid}.jpg",
            'original_filename' => 'example.jpg',
            'mime_type' => 'image/jpeg',
            'file_size' => fake()->numberBetween(10_000, 900_000),
            'width' => fake()->numberBetween(640, 2400),
            'height' => fake()->numberBetween(480, 1800),
            'checksum' => 'sha256:'.hash('sha256', $uuid),
            'status' => 'active',
        ];
    }
}

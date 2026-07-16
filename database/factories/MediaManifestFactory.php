<?php

namespace Database\Factories;

use App\Models\CatalogSnapshot;
use App\Models\MediaAsset;
use App\Models\MediaManifest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<MediaManifest> */
class MediaManifestFactory extends Factory
{
    protected $model = MediaManifest::class;

    public function definition(): array
    {
        $uuid = (string) Str::uuid();

        return [
            'catalog_snapshot_id' => CatalogSnapshot::factory(),
            'media_asset_id' => MediaAsset::factory(),
            'asset_uuid' => $uuid,
            'original_path' => "media/originals/{$uuid}.jpg",
            'variants_json' => [],
            'checksum' => hash('sha256', $uuid),
            'file_size' => fake()->numberBetween(10_000, 900_000),
            'mime_type' => 'image/jpeg',
            'status' => 'pending',
            'metadata_json' => [],
        ];
    }

    public function verified(): static
    {
        return $this->state(fn (): array => ['status' => 'verified']);
    }

    public function missing(): static
    {
        return $this->state(fn (): array => ['status' => 'missing']);
    }

    public function checksumMismatch(): static
    {
        return $this->state(fn (): array => ['status' => 'checksum_mismatch']);
    }
}

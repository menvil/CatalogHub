<?php

namespace Tests\Feature\Export;

use App\Models\CatalogSnapshot;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\MediaManifest;
use App\Models\MediaSource;
use App\Models\MediaVariant;
use App\Services\Export\MediaManifestJsonlExporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaManifestJsonlExporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_media_manifest_and_creates_snapshot_manifest_records(): void
    {
        Storage::fake('local');
        $asset = MediaAsset::factory()->create([
            'disk' => 'media',
            'original_path' => 'originals/product.jpg',
            'checksum' => 'sha256:expected',
        ]);
        MediaVariant::factory()->for($asset, 'asset')->create([
            'variant_type' => 'card',
            'path' => 'variants/card.webp',
        ]);
        MediaSource::factory()->for($asset, 'asset')->create([
            'source_url' => 'https://manufacturer.example/image.jpg',
        ]);
        MediaAssignment::factory()->for($asset, 'asset')->create(['role' => 'main']);
        MediaAsset::factory()->create();
        $snapshot = CatalogSnapshot::factory()->create(['storage_disk' => 'local']);

        $result = app(MediaManifestJsonlExporter::class)->export($snapshot);
        $rows = collect(explode("\n", trim(Storage::disk($result->disk)->get($result->path))))
            ->map(fn (string $line): array => json_decode($line, true, flags: JSON_THROW_ON_ERROR));
        $exported = $rows->firstWhere('asset_id', $asset->id);

        $this->assertSame(2, $result->lineCount);
        $this->assertSame('originals/product.jpg', $exported['original_path']);
        $this->assertSame('media', $exported['original_disk']);
        $this->assertSame('variants/card.webp', $exported['variants'][0]['path']);
        $this->assertSame('sha256:expected', $exported['checksum']);
        $this->assertSame('https://manufacturer.example/image.jpg', $exported['source_url']);
        $this->assertDatabaseCount('media_manifests', 2);
        $this->assertDatabaseHas('media_manifests', [
            'catalog_snapshot_id' => $snapshot->id,
            'media_asset_id' => $asset->id,
            'original_path' => 'originals/product.jpg',
        ]);
        $this->assertSame(2, MediaManifest::query()->whereBelongsTo($snapshot, 'catalogSnapshot')->count());
    }
}

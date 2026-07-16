<?php

namespace Tests\Feature\Console;

use App\Models\MediaAsset;
use App\Models\MediaVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaIntegrityCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_detects_missing_originals_and_variants_without_modifying_assets(): void
    {
        Storage::fake('media');
        $asset = MediaAsset::factory()->create([
            'disk' => 'media',
            'original_path' => 'missing/original.jpg',
            'status' => 'active',
        ]);
        MediaVariant::factory()->for($asset, 'asset')->create([
            'disk' => 'media',
            'path' => 'missing/card.webp',
        ]);

        $this->artisan('cataloghub:media-integrity-check')
            ->expectsOutputToContain('missing/original.jpg')
            ->expectsOutputToContain('missing/card.webp')
            ->expectsOutputToContain('2 missing file')
            ->assertExitCode(1);

        $this->assertSame('active', $asset->fresh()->status);
        $this->assertDatabaseHas('media_manifests', [
            'catalog_snapshot_id' => null,
            'media_asset_id' => $asset->id,
            'status' => 'missing',
        ]);
    }

    public function test_command_passes_valid_assets_and_records_verified_report(): void
    {
        Storage::fake('media');
        $asset = MediaAsset::factory()->create([
            'disk' => 'media',
            'original_path' => 'originals/valid.jpg',
        ]);
        $variant = MediaVariant::factory()->for($asset, 'asset')->create([
            'disk' => 'media',
            'path' => 'variants/valid-card.webp',
        ]);
        Storage::disk('media')->put($asset->original_path, 'original');
        Storage::disk('media')->put($variant->path, 'variant');

        $this->artisan('cataloghub:media-integrity-check')
            ->expectsOutputToContain('All 2 media files are present')
            ->assertExitCode(0);

        $this->assertDatabaseHas('media_manifests', [
            'catalog_snapshot_id' => null,
            'media_asset_id' => $asset->id,
            'status' => 'verified',
        ]);
    }
}

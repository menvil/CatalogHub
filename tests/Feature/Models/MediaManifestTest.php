<?php

namespace Tests\Feature\Models;

use App\Models\CatalogSnapshot;
use App\Models\MediaAsset;
use App\Models\MediaManifest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaManifestTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_manifest_with_snapshot_asset_relations_and_casts(): void
    {
        $snapshot = CatalogSnapshot::factory()->create();
        $asset = MediaAsset::factory()->create();
        $manifest = MediaManifest::factory()
            ->for($snapshot, 'catalogSnapshot')
            ->for($asset, 'mediaAsset')
            ->create([
                'variants_json' => [['type' => 'card', 'path' => 'variants/card.webp']],
                'metadata_json' => ['disk' => 'media'],
                'file_size' => 1234,
            ]);

        $this->assertTrue($manifest->exists);
        $this->assertTrue($manifest->catalogSnapshot->is($snapshot));
        $this->assertTrue($manifest->mediaAsset->is($asset));
        $this->assertSame('variants/card.webp', $manifest->variants_json[0]['path']);
        $this->assertSame(['disk' => 'media'], $manifest->metadata_json);
        $this->assertSame(1234, $manifest->file_size);
    }
}

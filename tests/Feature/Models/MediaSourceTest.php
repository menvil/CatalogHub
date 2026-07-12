<?php

namespace Tests\Feature\Models;

use App\Models\MediaAsset;
use App\Models\MediaSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaSourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_media_asset_and_casts_metadata(): void
    {
        $asset = MediaAsset::factory()->create();
        $source = MediaSource::factory()->for($asset, 'asset')->create([
            'metadata' => ['provider' => 'manufacturer'],
        ]);

        $this->assertTrue($source->asset->is($asset));
        $this->assertSame(['provider' => 'manufacturer'], $source->metadata);
    }
}

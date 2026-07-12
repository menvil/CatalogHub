<?php

namespace Tests\Feature\Models;

use App\Models\MediaAsset;
use App\Models\MediaVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaVariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_belongs_to_media_asset(): void
    {
        $asset = MediaAsset::factory()->create();
        $variant = MediaVariant::factory()->for($asset, 'asset')->create([
            'variant_type' => 'thumbnail',
        ]);

        $this->assertTrue($variant->asset->is($asset));
        $this->assertSame('thumbnail', $variant->variant_type);
    }
}

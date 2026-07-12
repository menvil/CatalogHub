<?php

namespace Tests\Feature\Models;

use App\Models\MediaAsset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaAssetTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_media_asset(): void
    {
        $asset = MediaAsset::factory()->create([
            'type' => 'image',
            'disk' => 'public',
            'original_path' => 'media/originals/test.jpg',
        ]);

        $this->assertTrue($asset->exists);
        $this->assertSame('image', $asset->type);
        $this->assertNotNull($asset->uuid);
    }

    public function test_has_media_relationships(): void
    {
        $asset = MediaAsset::factory()->create();

        $this->assertCount(0, $asset->variants);
        $this->assertCount(0, $asset->assignments);
        $this->assertCount(0, $asset->sources);
    }
}

<?php

namespace Tests\Feature\Services;

use App\Models\MediaAsset;
use App\Models\MediaVariant;
use App\Services\Media\MediaUrlGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUrlGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_url_for_media_variant_and_asset(): void
    {
        Storage::fake('public');

        $asset = MediaAsset::factory()->create([
            'disk' => 'public',
            'original_path' => 'media/originals/test.jpg',
        ]);
        $variant = MediaVariant::factory()->for($asset, 'asset')->create([
            'disk' => 'public',
            'path' => 'media/variants/test.webp',
        ]);

        $generator = app(MediaUrlGenerator::class);

        $this->assertStringContainsString('media/originals/test.jpg', $generator->forAsset($asset));
        $this->assertStringContainsString('media/variants/test.webp', $generator->forVariant($variant));
        $this->assertStringContainsString('media-placeholder.svg', $generator->placeholder());
    }
}

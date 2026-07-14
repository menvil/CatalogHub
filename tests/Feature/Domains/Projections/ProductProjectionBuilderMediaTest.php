<?php

namespace Tests\Feature\Domains\Projections;

use App\Domains\Projections\Builders\ProductProjectionBuilder;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductProjectionBuilderMediaTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_adds_resolved_site_media_and_gallery_to_the_projection(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create(['name' => 'Projection Product']);
        $globalAsset = MediaAsset::factory()->create(['original_path' => 'media/global.jpg']);
        $siteAsset = MediaAsset::factory()->create([
            'original_path' => 'media/site-main.jpg',
            'width' => 800,
            'height' => 600,
        ]);
        $galleryAsset = MediaAsset::factory()->create(['original_path' => 'media/gallery.jpg']);

        MediaAssignment::factory()->for($globalAsset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => $product->id,
            'role' => 'main',
        ]);
        MediaAssignment::factory()->for($siteAsset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => $product->id,
            'role' => 'main',
            'locale' => 'en',
            'site_id' => $site->id,
            'market_id' => $site->market_id,
        ]);
        MediaAssignment::factory()->for($galleryAsset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => $product->id,
            'role' => 'gallery',
        ]);

        $projection = app(ProductProjectionBuilder::class)->build($site, $product, 'en');

        $this->assertSame($siteAsset->id, $projection->media['main']['asset_id']);
        $this->assertStringContainsString('media/site-main.jpg', $projection->media['main']['url']);
        $this->assertSame('Projection Product', $projection->media['main']['alt']);
        $this->assertSame(800, $projection->media['main']['width']);
        $this->assertSame($galleryAsset->id, $projection->media['gallery'][0]['asset_id']);
        $this->assertSame($projection->media, $projection->payload['media']);
    }

    public function test_it_uses_an_explicit_placeholder_payload_when_media_is_missing(): void
    {
        $projection = app(ProductProjectionBuilder::class)->build(
            Site::factory()->create(),
            CentralProduct::factory()->create(),
            'en',
        );

        $this->assertTrue($projection->media['main']['is_placeholder']);
        $this->assertNotSame('', $projection->media['main']['url']);
        $this->assertSame([], $projection->media['gallery']);
    }
}

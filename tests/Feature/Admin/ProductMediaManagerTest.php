<?php

namespace Tests\Feature\Admin;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMediaManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_allows_central_admin_to_view_product_media_manager(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $product = CentralProduct::factory()->create();

        $this->actingAs($admin)
            ->get("/central/products/{$product->id}/media")
            ->assertOk()
            ->assertSee('Product Media')
            ->assertSee($product->name);
    }

    public function test_assigns_media_asset_to_product_with_role(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $product = CentralProduct::factory()->create();
        $asset = MediaAsset::factory()->create();

        $this->actingAs($admin)
            ->post(route('central.products.media.assign', $product), [
                'media_asset_id' => $asset->id,
                'role' => 'main',
            ])
            ->assertRedirect(route('central.products.media', $product));

        $this->assertDatabaseHas('media_assignments', [
            'media_asset_id' => $asset->id,
            'entity_type' => 'central_product',
            'entity_id' => $product->id,
            'role' => 'main',
            'locale' => null,
        ]);
    }

    public function test_assigns_localized_site_and_market_scoped_media_asset_to_product(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $product = CentralProduct::factory()->create();
        $asset = MediaAsset::factory()->create();

        $this->actingAs($admin)
            ->post(route('central.products.media.assign', $product), [
                'media_asset_id' => $asset->id,
                'role' => 'hero',
                'locale' => 'de-DE',
                'site_id' => 10,
                'market_id' => 5,
            ])
            ->assertRedirect(route('central.products.media', $product));

        $this->assertDatabaseHas('media_assignments', [
            'media_asset_id' => $asset->id,
            'entity_type' => 'central_product',
            'entity_id' => $product->id,
            'role' => 'hero',
            'locale' => 'de-DE',
            'site_id' => 10,
            'market_id' => 5,
        ]);
    }

    public function test_shows_media_fallback_preview_for_product_role(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $product = CentralProduct::factory()->create();
        $asset = MediaAsset::factory()->create(['original_filename' => 'main.jpg']);

        MediaAssignment::factory()->for($asset, 'asset')->create([
            'entity_type' => 'central_product',
            'entity_id' => $product->id,
            'role' => 'main',
        ]);

        $this->actingAs($admin)
            ->get(route('central.products.media', ['product' => $product, 'preview_role' => 'main']))
            ->assertOk()
            ->assertSee('Resolved media')
            ->assertSee('main.jpg')
            ->assertSee('alt="main.jpg"', false);
    }
}

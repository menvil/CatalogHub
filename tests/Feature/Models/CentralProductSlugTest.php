<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralProductSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_generates_central_product_slug_from_name(): void
    {
        $product = CentralProduct::factory()->create([
            'name' => 'LG UltraGear 27GP850-B',
            'model' => null,
            'slug' => null,
        ]);

        $this->assertSame('lg-ultragear-27gp850-b', $product->slug);
    }

    public function test_generates_central_product_slug_from_name_and_model(): void
    {
        $product = CentralProduct::factory()->create([
            'name' => 'LG UltraGear',
            'model' => '27GP850-B',
            'slug' => null,
        ]);

        $this->assertSame('lg-ultragear-27gp850-b', $product->slug);
    }

    public function test_generates_unique_central_product_slugs(): void
    {
        CentralProduct::factory()->create([
            'name' => 'LG UltraGear',
            'model' => null,
            'slug' => null,
        ]);

        $second = CentralProduct::factory()->create([
            'name' => 'LG UltraGear',
            'model' => null,
            'slug' => null,
        ]);

        $this->assertSame('lg-ultragear-2', $second->slug);
    }

    public function test_does_not_overwrite_manual_central_product_slug(): void
    {
        $product = CentralProduct::factory()->create([
            'name' => 'LG UltraGear',
            'slug' => 'custom-product-slug',
        ]);

        $this->assertSame('custom-product-slug', $product->slug);
    }

    public function test_generates_central_product_slug_with_brand_prefix(): void
    {
        $brand = CentralBrand::factory()->create([
            'name' => 'LG',
            'slug' => 'lg',
        ]);

        $product = CentralProduct::factory()->create([
            'central_brand_id' => $brand->id,
            'name' => 'UltraGear',
            'model' => '27GP850-B',
            'slug' => null,
        ]);

        $this->assertSame('lg-ultragear-27gp850-b', $product->slug);
    }

    public function test_regenerates_slug_with_current_brand_when_loaded_brand_is_stale(): void
    {
        $oldBrand = CentralBrand::factory()->create([
            'name' => 'Old Brand',
            'slug' => 'old-brand',
        ]);
        $newBrand = CentralBrand::factory()->create([
            'name' => 'New Brand',
            'slug' => 'new-brand',
        ]);

        $product = CentralProduct::factory()->create([
            'central_brand_id' => $oldBrand->id,
            'name' => 'Monitor',
            'model' => null,
            'slug' => 'old-brand-monitor',
        ]);

        $product->load('brand');
        $product->central_brand_id = $newBrand->id;
        $product->slug = null;
        $product->save();

        $this->assertSame('new-brand-monitor', $product->slug);
    }

    public function test_generated_slug_is_limited_to_database_column_length(): void
    {
        $product = CentralProduct::factory()->create([
            'name' => str_repeat('Very Long Product Name ', 20),
            'model' => null,
            'slug' => null,
        ]);

        $this->assertLessThanOrEqual(255, strlen($product->slug));
    }

    public function test_retries_when_auto_generated_slug_hits_unique_constraint(): void
    {
        CentralProduct::factory()->create([
            'name' => 'Existing Product',
            'model' => null,
            'slug' => 'collision-slug',
        ]);

        $attempts = 0;

        CentralProduct::saving(function (CentralProduct $product) use (&$attempts): void {
            if ($product->exists || $attempts > 0) {
                return;
            }

            $attempts++;
            $product->slug = 'collision-slug';
        });

        $product = CentralProduct::factory()->create([
            'name' => 'Collision Slug',
            'model' => null,
            'slug' => null,
        ]);

        $this->assertSame('collision-slug-2', $product->slug);
    }
}

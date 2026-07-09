<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralProductBrandRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_products_have_central_brand_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('central_products', 'central_brand_id'));
    }

    public function test_product_belongs_to_central_brand(): void
    {
        $brand = CentralBrand::factory()->create();
        $product = CentralProduct::factory()->create([
            'central_brand_id' => $brand->id,
        ]);

        $this->assertSame($brand->id, $product->brand->id);
    }

    public function test_brand_has_central_products(): void
    {
        $brand = CentralBrand::factory()->create();

        CentralProduct::factory()->count(2)->create([
            'central_brand_id' => $brand->id,
        ]);

        $this->assertCount(2, $brand->products);
    }

    public function test_product_brand_id_becomes_null_when_brand_is_deleted(): void
    {
        $brand = CentralBrand::factory()->create();
        $product = CentralProduct::factory()->create([
            'central_brand_id' => $brand->id,
        ]);

        $brand->delete();

        $this->assertNull($product->fresh()->central_brand_id);
    }

    public function test_generated_product_slug_can_include_brand_name(): void
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
}

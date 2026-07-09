<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralProductCategoryRelationTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_products_have_central_category_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('central_products', 'central_category_id'));
    }

    public function test_product_belongs_to_central_category(): void
    {
        $category = CentralCategory::factory()->create();
        $product = CentralProduct::factory()->create([
            'central_category_id' => $category->id,
        ]);

        $this->assertSame($category->id, $product->category->id);
    }

    public function test_category_has_central_products(): void
    {
        $category = CentralCategory::factory()->create();

        CentralProduct::factory()->count(2)->create([
            'central_category_id' => $category->id,
        ]);

        $this->assertCount(2, $category->products);
    }

    public function test_product_category_id_becomes_null_when_category_is_deleted(): void
    {
        $category = CentralCategory::factory()->create();
        $product = CentralProduct::factory()->create([
            'central_category_id' => $category->id,
        ]);

        $category->delete();

        $this->assertNull($product->fresh()->central_category_id);
    }
}

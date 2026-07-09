<?php

namespace Tests\Feature\Database;

use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralProductVariantsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_central_product_variants_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('central_product_variants'));
        $this->assertTrue(Schema::hasColumns('central_product_variants', [
            'id',
            'central_product_id',
            'name',
            'sku',
            'status',
            'position',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_central_product_variants_indexes_exist(): void
    {
        $indexes = collect(Schema::getIndexes('central_product_variants'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['sku']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['status']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['position']
        ));
    }

    public function test_central_product_variant_belongs_to_existing_product(): void
    {
        $product = CentralProduct::factory()->create();

        DB::table('central_product_variants')->insert([
            'central_product_id' => $product->id,
            'name' => '256GB Black',
            'sku' => 'SKU-256-BLK',
            'status' => 'draft',
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('central_product_variants', [
            'central_product_id' => $product->id,
            'sku' => 'SKU-256-BLK',
        ]);
    }

    public function test_central_product_variants_are_deleted_when_product_is_deleted(): void
    {
        $product = CentralProduct::factory()->create();

        DB::table('central_product_variants')->insert([
            'central_product_id' => $product->id,
            'name' => '256GB Black',
            'sku' => 'SKU-256-BLK',
            'status' => 'draft',
            'position' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $product->delete();

        $this->assertDatabaseMissing('central_product_variants', [
            'central_product_id' => $product->id,
            'sku' => 'SKU-256-BLK',
        ]);
    }
}

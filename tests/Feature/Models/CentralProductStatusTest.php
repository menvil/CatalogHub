<?php

namespace Tests\Feature\Models;

use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralProductStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_central_product_status(): void
    {
        $product = CentralProduct::factory()->create([
            'status' => CentralProductStatus::Draft,
        ]);

        $this->assertSame(CentralProductStatus::Draft, $product->status);
    }

    public function test_central_product_status_defaults_to_draft(): void
    {
        CentralProduct::query()->create([
            'name' => 'LG UltraGear',
            'slug' => 'lg-ultragear',
        ]);

        $this->assertSame(CentralProductStatus::Draft, CentralProduct::first()->status);
    }

    public function test_central_product_status_is_indexed(): void
    {
        $indexes = collect(Schema::getIndexes('central_products'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['status']
        ));
    }
}

<?php

namespace Tests\Feature\Models;

use App\Enums\CentralProductVariantStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralProductVariantTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_central_product_variant_for_product(): void
    {
        $product = CentralProduct::factory()->create();

        $variant = CentralProductVariant::factory()->create([
            'central_product_id' => $product->id,
            'name' => '256GB Black',
        ]);

        $this->assertSame($product->id, $variant->product->id);
        $this->assertCount(1, $product->variants);
    }

    public function test_central_product_variant_status_casts_to_enum(): void
    {
        $variant = CentralProductVariant::factory()->create([
            'status' => CentralProductVariantStatus::Active,
        ]);

        $this->assertSame(CentralProductVariantStatus::Active, $variant->status);
    }
}

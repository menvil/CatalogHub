<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CentralProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_central_product(): void
    {
        $product = CentralProduct::factory()->create([
            'name' => 'LG UltraGear 27GP850-B',
            'slug' => 'lg-ultragear-27gp850-b',
        ]);

        $this->assertTrue($product->exists);
        $this->assertSame('LG UltraGear 27GP850-B', $product->name);
        $this->assertSame('lg-ultragear-27gp850-b', $product->slug);
    }

    public function test_can_store_optional_central_product_model_identifier(): void
    {
        $product = CentralProduct::factory()->create([
            'model' => '27GP850-B',
        ]);

        $this->assertSame('27GP850-B', $product->model);
    }
}

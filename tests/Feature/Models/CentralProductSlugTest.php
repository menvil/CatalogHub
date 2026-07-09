<?php

namespace Tests\Feature\Models;

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
}

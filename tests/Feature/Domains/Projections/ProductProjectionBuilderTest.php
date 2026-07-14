<?php

namespace Tests\Feature\Domains\Projections;

use App\Domains\Projections\Builders\ProductProjectionBuilder;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductProjectionBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_deterministic_base_product_data_without_mutating_central_data(): void
    {
        $site = Site::factory()->create(['code' => 'de-monitors']);
        $brand = CentralBrand::factory()->create([
            'name' => 'LG',
            'slug' => 'lg',
        ]);
        $category = CentralCategory::factory()->create([
            'name' => 'Monitors',
            'slug' => 'monitors',
        ]);
        $product = CentralProduct::factory()
            ->for($brand, 'brand')
            ->for($category, 'category')
            ->create([
                'name' => 'LG UltraGear 27GP850-B',
                'model' => '27GP850-B',
                'slug' => 'lg-ultragear-27gp850-b',
                'status' => CentralProductStatus::Active,
            ]);
        $originalUpdatedAt = $product->updated_at;

        $builder = app(ProductProjectionBuilder::class);
        $first = $builder->build($site, $product, 'en');
        $second = $builder->build($site, $product, 'en');

        $this->assertSame($product->id, $first->payload['product']['id']);
        $this->assertSame('LG UltraGear 27GP850-B', $first->payload['product']['title']);
        $this->assertSame('27GP850-B', $first->payload['product']['model']);
        $this->assertSame('active', $first->payload['product']['status']);
        $this->assertSame($brand->id, $first->payload['brand']['id']);
        $this->assertSame('LG', $first->payload['brand']['name']);
        $this->assertSame($category->id, $first->payload['category']['id']);
        $this->assertSame('Monitors', $first->payload['category']['name']);
        $this->assertSame($site->id, $first->payload['site']['id']);
        $this->assertSame('en', $first->payload['site']['locale']);
        $this->assertSame($first->checksum, $second->checksum);
        $this->assertNotNull($first->checksum);
        $this->assertTrue($product->fresh()->updated_at->equalTo($originalUpdatedAt));
    }
}

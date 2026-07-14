<?php

namespace Tests\Feature\Domains\Projections;

use App\Domains\Projections\Builders\ProductProjectionBuilder;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
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

    public function test_it_adds_visible_product_specs_grouped_and_ordered_by_attribute_sections(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $display = AttributeSection::factory()->for($category, 'category')->create([
            'code' => 'display',
            'name' => 'Display',
            'position' => 2,
        ]);
        $connectivity = AttributeSection::factory()->for($category, 'category')->create([
            'code' => 'connectivity',
            'name' => 'Connectivity',
            'position' => 1,
        ]);
        $refreshRate = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($display, 'section')
            ->create([
                'code' => 'refresh_rate',
                'name' => 'Refresh rate',
                'data_type' => 'integer',
                'canonical_unit' => 'hertz',
                'position' => 1,
                'is_filterable' => true,
            ]);
        $brightness = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($display, 'section')
            ->create([
                'code' => 'brightness',
                'name' => 'Brightness',
                'data_type' => 'integer',
                'position' => 2,
            ]);
        $ports = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($connectivity, 'section')
            ->create([
                'code' => 'ports',
                'name' => 'Ports',
                'data_type' => 'string',
            ]);
        $hidden = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($display, 'section')
            ->create(['code' => 'internal_code', 'is_visible' => false]);
        $product = CentralProduct::factory()->for($category, 'category')->create();

        CentralProductAttributeValue::factory()->for($product, 'product')->for($refreshRate, 'attributeDefinition')->create([
            'value_type' => 'integer',
            'value_number' => 165,
            'canonical_value' => 165,
            'canonical_unit' => 'hertz',
        ]);
        CentralProductAttributeValue::factory()->for($product, 'product')->for($brightness, 'attributeDefinition')->create([
            'value_type' => 'integer',
            'value_number' => 400,
            'canonical_value' => 400,
        ]);
        CentralProductAttributeValue::factory()->for($product, 'product')->for($ports, 'attributeDefinition')->create([
            'value_type' => 'string',
            'value_text' => 'HDMI, DisplayPort',
        ]);
        CentralProductAttributeValue::factory()->for($product, 'product')->for($hidden, 'attributeDefinition')->create();

        $projection = app(ProductProjectionBuilder::class)->build($site, $product, 'en');
        $sections = $projection->payload['spec_sections'];

        $this->assertSame(['connectivity', 'display'], array_column($sections, 'code'));
        $this->assertSame('ports', $sections[0]['attributes'][0]['code']);
        $this->assertSame(['refresh_rate', 'brightness'], array_column($sections[1]['attributes'], 'code'));
        $this->assertSame(165, $sections[1]['attributes'][0]['canonical_value']);
        $this->assertSame('hertz', $sections[1]['attributes'][0]['canonical_unit']);
        $this->assertNull($sections[1]['attributes'][0]['display_value']);
        $this->assertTrue($sections[1]['attributes'][0]['is_filterable']);
        $this->assertNotContains('internal_code', array_column($sections[1]['attributes'], 'code'));
    }
}

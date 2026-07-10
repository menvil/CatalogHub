<?php

namespace Tests\Feature\Services;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Services\ProductAttributes\GroupedSpecsPreviewBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupedSpecsPreviewBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_grouped_preview_with_existing_values(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create([
            'name' => 'Display',
            'code' => 'display',
        ]);
        $product = CentralProduct::factory()->for($category, 'category')->create();
        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'name' => 'Refresh rate',
                'code' => 'refresh_rate',
                'data_type' => 'decimal',
                'canonical_unit' => 'hertz',
            ]);

        CentralProductAttributeValue::factory()
            ->for($product, 'product')
            ->for($attribute, 'attributeDefinition')
            ->create([
                'value_type' => 'decimal',
                'canonical_value' => 165,
                'canonical_unit' => 'hertz',
            ]);

        $preview = app(GroupedSpecsPreviewBuilder::class)->build($product);

        $this->assertSame('Display', $preview[0]['section']);
        $this->assertSame('Refresh rate', $preview[0]['attributes'][0]['name']);
        $this->assertSame('165.000000 hertz', $preview[0]['attributes'][0]['value']);
    }
}

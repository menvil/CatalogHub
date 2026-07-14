<?php

namespace Tests\Unit\Services;

use App\Enums\AttributeDataType;
use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Models\FacetOption;
use App\Services\Facets\CategoryFacetConfigResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryFacetConfigResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_only_active_visible_facets_in_position_order(): void
    {
        $category = CentralCategory::factory()->create();
        $later = FacetDefinition::factory()->for($category, 'category')->active()->create([
            'code' => 'brand',
            'position' => 20,
        ]);
        $earlier = FacetDefinition::factory()->for($category, 'category')->active()->create([
            'code' => 'panel_type',
            'position' => 10,
        ]);
        FacetDefinition::factory()->for($category, 'category')->inactive()->create(['position' => 0]);
        FacetDefinition::factory()->for($category, 'category')->active()->create([
            'is_visible' => false,
            'position' => 5,
        ]);

        $facets = app(CategoryFacetConfigResolver::class)->resolve($category);

        $this->assertCount(2, $facets);
        $this->assertSame($earlier->id, $facets->first()->id);
        $this->assertSame($later->id, $facets->last()->id);
    }

    public function test_includes_active_options_and_attribute_metadata(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'code' => 'panel_type',
            'name' => 'Panel type',
            'data_type' => AttributeDataType::Enum,
        ]);
        $facet = FacetDefinition::factory()->create([
            'category_id' => $attribute->central_category_id,
            'attribute_definition_id' => $attribute->id,
            'code' => 'panel_type',
            'label_override' => 'Display technology',
            'facet_type' => FacetType::Checkbox,
            'source_type' => FacetSourceType::Attribute,
        ]);
        FacetOption::factory()->for($facet)->create([
            'value' => 'ips',
            'label_override' => 'IPS panel',
            'position' => 10,
        ]);
        FacetOption::factory()->for($facet)->create([
            'value' => 'va',
            'is_active' => false,
            'position' => 20,
        ]);

        $resolved = app(CategoryFacetConfigResolver::class)
            ->resolve($attribute->category)
            ->sole();

        $this->assertSame('Display technology', $resolved->label);
        $this->assertSame('panel_type', $resolved->attributeCode);
        $this->assertSame(AttributeDataType::Enum, $resolved->attributeDataType);
        $this->assertCount(1, $resolved->options);
        $this->assertSame('IPS panel', $resolved->options[0]->label);
    }
}

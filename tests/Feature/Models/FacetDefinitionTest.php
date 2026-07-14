<?php

namespace Tests\Feature\Models;

use App\Enums\AttributeDataType;
use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Models\FacetOption;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FacetDefinitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_facet_definition_belongs_to_category_and_optional_attribute(): void
    {
        $attribute = AttributeDefinition::factory()->create();
        $facet = FacetDefinition::factory()->create([
            'category_id' => $attribute->central_category_id,
            'attribute_definition_id' => $attribute->id,
        ]);

        $this->assertTrue($facet->category->is($attribute->category));
        $this->assertTrue($facet->attributeDefinition->is($attribute));
    }

    public function test_facet_definition_has_many_ordered_options(): void
    {
        $facet = FacetDefinition::factory()->create();
        $later = FacetOption::factory()->for($facet)->create(['position' => 20]);
        $earlier = FacetOption::factory()->for($facet)->create(['position' => 10]);

        $this->assertCount(2, $facet->options);
        $this->assertTrue($facet->options->first()->is($earlier));
        $this->assertTrue($facet->options->last()->is($later));
        $this->assertTrue($earlier->facetDefinition->is($facet));
    }

    public function test_facet_option_value_rejects_comma_separator(): void
    {
        $this->expectException(ValidationException::class);

        FacetOption::factory()->create(['value' => 'usb-c,thunderbolt']);
    }

    public function test_facet_definition_casts_enums_flags_and_config(): void
    {
        $facet = FacetDefinition::factory()->create([
            'facet_type' => FacetType::Checkbox,
            'source_type' => FacetSourceType::Attribute,
            'is_active' => 1,
            'config_json' => ['searchable' => true],
        ]);

        $this->assertSame(FacetType::Checkbox, $facet->facet_type);
        $this->assertSame(FacetSourceType::Attribute, $facet->source_type);
        $this->assertTrue($facet->is_active);
        $this->assertSame(['searchable' => true], $facet->config_json);
    }

    public function test_factory_supports_checkbox_facet_type(): void
    {
        $facet = FacetDefinition::factory()->checkbox()->create();

        $this->assertSame(FacetType::Checkbox, $facet->facet_type);
        $this->assertTrue($facet->facet_type->acceptsMultipleValues());
    }

    public function test_factory_supports_range_facet_config(): void
    {
        $facet = FacetDefinition::factory()->range()->create([
            'config_json' => ['min' => 0, 'max' => 240, 'step' => 1, 'unit_code' => 'hz'],
        ]);

        $this->assertSame(FacetType::Range, $facet->facet_type);
        $this->assertSame(240, $facet->config_json['max']);
    }

    public function test_factory_supports_boolean_facet_type(): void
    {
        $facet = FacetDefinition::factory()->boolean()->create();

        $this->assertSame(FacetType::Boolean, $facet->facet_type);
        $this->assertSame(AttributeDataType::Boolean, $facet->attributeDefinition->data_type);
        $this->assertTrue($facet->attributeDefinition->category->is($facet->category));
    }

    public function test_factory_supports_single_value_select_facet_type(): void
    {
        $facet = FacetDefinition::factory()->select()->create();

        $this->assertSame(FacetType::Select, $facet->facet_type);
        $this->assertFalse($facet->facet_type->acceptsMultipleValues());
    }

    public function test_facets_can_be_scoped_to_category_and_ordered_by_position(): void
    {
        $category = CentralCategory::factory()->create();
        $otherCategory = CentralCategory::factory()->create();
        $later = FacetDefinition::factory()->for($category, 'category')->create(['position' => 20]);
        $earlier = FacetDefinition::factory()->for($category, 'category')->create(['position' => 10]);
        FacetDefinition::factory()->for($otherCategory, 'category')->create(['position' => 0]);

        $facets = FacetDefinition::query()->forCategory($category)->ordered()->get();

        $this->assertCount(2, $facets);
        $this->assertTrue($facets->first()->is($earlier));
        $this->assertTrue($facets->last()->is($later));
    }

    public function test_active_scope_excludes_inactive_facets(): void
    {
        $active = FacetDefinition::factory()->active()->create();
        FacetDefinition::factory()->inactive()->create();

        $facets = FacetDefinition::query()->active()->get();

        $this->assertCount(1, $facets);
        $this->assertTrue($facets->first()->is($active));
    }
}

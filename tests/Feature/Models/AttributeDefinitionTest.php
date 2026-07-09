<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeDefinitionTest extends TestCase
{
    use RefreshDatabase;

    public function test_attribute_definition_belongs_to_category_and_section(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();

        $attribute = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create();

        $this->assertTrue($attribute->category->is($category));
        $this->assertTrue($attribute->section->is($section));
    }

    public function test_attribute_definition_flags_are_cast_to_booleans(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'is_required' => 1,
            'is_filterable' => 1,
            'is_sortable' => 1,
            'is_comparable' => 1,
            'is_visible' => 1,
            'is_searchable' => 1,
        ]);

        $this->assertTrue($attribute->is_required);
        $this->assertTrue($attribute->is_filterable);
        $this->assertTrue($attribute->is_sortable);
        $this->assertTrue($attribute->is_comparable);
        $this->assertTrue($attribute->is_visible);
        $this->assertTrue($attribute->is_searchable);
    }
}

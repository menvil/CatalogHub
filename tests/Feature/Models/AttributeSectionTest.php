<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_attribute_section_belongs_to_category(): void
    {
        $category = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($category, 'category')->create();

        $this->assertTrue($section->category->is($category));
    }

    public function test_section_model_has_no_nested_relationships(): void
    {
        $methods = get_class_methods(new AttributeSection);
        self::assertNotContains('parent', $methods);
        self::assertNotContains('children', $methods);
        self::assertNotContains('parent_id', (new AttributeSection)->getFillable());
    }

    public function test_attribute_section_flags_are_cast_to_booleans(): void
    {
        $section = AttributeSection::factory()->create([
            'is_collapsible' => 1,
            'is_visible' => 1,
        ]);

        $this->assertTrue($section->is_collapsible);
        $this->assertTrue($section->is_visible);

        $hiddenSection = AttributeSection::factory()->create([
            'is_collapsible' => 0,
            'is_visible' => 0,
        ]);

        $this->assertFalse($hiddenSection->is_collapsible);
        $this->assertFalse($hiddenSection->is_visible);
    }
}

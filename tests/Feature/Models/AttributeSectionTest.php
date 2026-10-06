<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Database\QueryException;
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

    public function test_target_section_rejects_nested_shape_at_database_boundary(): void
    {
        $parent = AttributeSection::factory()->create();
        $this->expectException(QueryException::class);
        AttributeSection::factory()->create(['central_category_id' => $parent->central_category_id, 'parent_id' => $parent->id]);
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

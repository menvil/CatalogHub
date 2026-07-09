<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeSectionOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_attribute_sections_by_position_inside_category(): void
    {
        $category = CentralCategory::factory()->create();

        AttributeSection::factory()->for($category, 'category')->create(['name' => 'B', 'code' => 'b', 'position' => 2]);
        AttributeSection::factory()->for($category, 'category')->create(['name' => 'A', 'code' => 'a', 'position' => 1]);

        $names = $category->attributeSections()->ordered()->pluck('name')->all();

        $this->assertSame(['A', 'B'], $names);
    }

    public function test_section_ordering_uses_id_as_tie_breaker(): void
    {
        $category = CentralCategory::factory()->create();

        $first = AttributeSection::factory()->for($category, 'category')->create(['code' => 'first', 'position' => 1]);
        $second = AttributeSection::factory()->for($category, 'category')->create(['code' => 'second', 'position' => 1]);

        $ids = $category->attributeSections()->ordered()->pluck('id')->all();

        $this->assertSame([$first->id, $second->id], $ids);
    }
}

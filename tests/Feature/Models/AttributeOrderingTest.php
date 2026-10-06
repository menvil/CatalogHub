<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_orders_attributes_by_position_inside_section(): void
    {
        $section = AttributeSection::factory()->create();

        AttributeDefinition::factory()
            ->assignedTo($section->category)
            ->state(['attribute_section_id' => $section->id])
            ->create(['code' => 'b', 'position' => 2]);
        AttributeDefinition::factory()
            ->assignedTo($section->category)
            ->state(['attribute_section_id' => $section->id])
            ->create(['code' => 'a', 'position' => 1]);

        $codes = $section->assignments()->with('definition')->ordered()->get()->pluck('definition.code')->all();

        $this->assertSame(['a', 'b'], $codes);
    }

    public function test_attribute_ordering_uses_id_as_tie_breaker(): void
    {
        $section = AttributeSection::factory()->create();

        $first = AttributeDefinition::factory()
            ->assignedTo($section->category)
            ->state(['attribute_section_id' => $section->id])
            ->create(['code' => 'first', 'position' => 1]);
        $second = AttributeDefinition::factory()
            ->assignedTo($section->category)
            ->state(['attribute_section_id' => $section->id])
            ->create(['code' => 'second', 'position' => 1]);

        $ids = $section->assignments()->ordered()->pluck('attribute_definition_id')->all();

        $this->assertSame([$first->id, $second->id], $ids);
    }
}

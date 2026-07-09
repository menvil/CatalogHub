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
            ->for($section->category, 'category')
            ->for($section, 'section')
            ->create(['code' => 'b', 'position' => 2]);
        AttributeDefinition::factory()
            ->for($section->category, 'category')
            ->for($section, 'section')
            ->create(['code' => 'a', 'position' => 1]);

        $codes = $section->attributes()->ordered()->pluck('code')->all();

        $this->assertSame(['a', 'b'], $codes);
    }

    public function test_attribute_ordering_uses_id_as_tie_breaker(): void
    {
        $section = AttributeSection::factory()->create();

        $first = AttributeDefinition::factory()
            ->for($section->category, 'category')
            ->for($section, 'section')
            ->create(['code' => 'first', 'position' => 1]);
        $second = AttributeDefinition::factory()
            ->for($section->category, 'category')
            ->for($section, 'section')
            ->create(['code' => 'second', 'position' => 1]);

        $ids = $section->attributes()->ordered()->pluck('id')->all();

        $this->assertSame([$first->id, $second->id], $ids);
    }
}

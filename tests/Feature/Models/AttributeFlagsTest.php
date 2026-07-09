<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\AttributeDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeFlagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_attribute_visibility_and_searchable_flags_to_booleans(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'is_visible' => 1,
            'is_searchable' => 1,
        ]);

        $this->assertTrue($attribute->is_visible);
        $this->assertTrue($attribute->is_searchable);
    }

    public function test_visible_scope_returns_visible_attributes(): void
    {
        $visible = AttributeDefinition::factory()->create(['is_visible' => true]);
        AttributeDefinition::factory()->create(['is_visible' => false]);

        $ids = AttributeDefinition::query()->visible()->pluck('id')->all();

        $this->assertSame([$visible->id], $ids);
    }

    public function test_searchable_scope_returns_searchable_attributes(): void
    {
        $searchable = AttributeDefinition::factory()->create(['is_searchable' => true]);
        AttributeDefinition::factory()->create(['is_searchable' => false]);

        $ids = AttributeDefinition::query()->searchable()->pluck('id')->all();

        $this->assertSame([$searchable->id], $ids);
    }
}

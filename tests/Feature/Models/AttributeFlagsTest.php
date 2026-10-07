<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\CategoryAttributeAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeFlagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_attribute_visibility_and_searchable_flags_to_booleans(): void
    {
        $attribute = CategoryAttributeAssignment::factory()->create([
            'is_visible' => 1,
            'is_searchable' => 1,
        ]);

        $this->assertTrue($attribute->is_visible);
        $this->assertTrue($attribute->is_searchable);
    }

    public function test_visible_scope_returns_visible_attributes(): void
    {
        $visible = CategoryAttributeAssignment::factory()->create(['is_visible' => true]);
        CategoryAttributeAssignment::factory()->create(['is_visible' => false]);

        $ids = CategoryAttributeAssignment::query()->visible()->pluck('id')->all();

        $this->assertSame([$visible->id], $ids);
    }

    public function test_searchable_scope_returns_searchable_attributes(): void
    {
        $searchable = CategoryAttributeAssignment::factory()->create(['is_searchable' => true]);
        CategoryAttributeAssignment::factory()->create(['is_searchable' => false]);

        $ids = CategoryAttributeAssignment::query()->searchable()->pluck('id')->all();

        $this->assertSame([$searchable->id], $ids);
    }
}

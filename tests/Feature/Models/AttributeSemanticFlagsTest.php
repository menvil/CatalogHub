<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\CategoryAttributeAssignment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeSemanticFlagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_required_visible_sortable_and_searchable_flags_to_booleans(): void
    {
        $attribute = CategoryAttributeAssignment::factory()->create([
            'is_required' => 1,
            'is_visible' => 1,
            'is_sortable' => 1,
            'is_searchable' => 1,
        ]);

        $this->assertTrue($attribute->is_required);
        $this->assertTrue($attribute->is_visible);
        $this->assertTrue($attribute->is_sortable);
        $this->assertTrue($attribute->is_searchable);
    }

    public function test_semantic_flag_scopes_return_matching_attributes(): void
    {
        $required = CategoryAttributeAssignment::factory()->create(['is_required' => true, 'is_visible' => false]);
        $filterable = CategoryAttributeAssignment::factory()->create(['is_visible' => true]);
        $sortable = CategoryAttributeAssignment::factory()->create(['is_sortable' => true, 'is_visible' => false]);
        $comparable = CategoryAttributeAssignment::factory()->create(['is_searchable' => true, 'is_visible' => false]);
        CategoryAttributeAssignment::factory()->create([
            'is_required' => false,
            'is_visible' => false,
            'is_sortable' => false,
            'is_searchable' => false,
        ]);

        $this->assertSame([$required->id], CategoryAttributeAssignment::query()->required()->pluck('id')->all());
        $this->assertSame([$filterable->id], CategoryAttributeAssignment::query()->visible()->pluck('id')->all());
        $this->assertSame([$sortable->id], CategoryAttributeAssignment::query()->sortable()->pluck('id')->all());
        $this->assertSame([$comparable->id], CategoryAttributeAssignment::query()->searchable()->pluck('id')->all());
    }
}

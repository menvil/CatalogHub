<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\AttributeDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttributeSemanticFlagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_required_filterable_sortable_and_comparable_flags_to_booleans(): void
    {
        $attribute = AttributeDefinition::factory()->create([
            'is_required' => 1,
            'is_filterable' => 1,
            'is_sortable' => 1,
            'is_comparable' => 1,
        ]);

        $this->assertTrue($attribute->is_required);
        $this->assertTrue($attribute->is_filterable);
        $this->assertTrue($attribute->is_sortable);
        $this->assertTrue($attribute->is_comparable);
    }

    public function test_semantic_flag_scopes_return_matching_attributes(): void
    {
        $required = AttributeDefinition::factory()->create(['is_required' => true]);
        $filterable = AttributeDefinition::factory()->create(['is_filterable' => true]);
        $sortable = AttributeDefinition::factory()->create(['is_sortable' => true]);
        $comparable = AttributeDefinition::factory()->create(['is_comparable' => true]);
        AttributeDefinition::factory()->create([
            'is_required' => false,
            'is_filterable' => false,
            'is_sortable' => false,
            'is_comparable' => false,
        ]);

        $this->assertSame([$required->id], AttributeDefinition::query()->required()->pluck('id')->all());
        $this->assertSame([$filterable->id], AttributeDefinition::query()->filterable()->pluck('id')->all());
        $this->assertSame([$sortable->id], AttributeDefinition::query()->sortable()->pluck('id')->all());
        $this->assertSame([$comparable->id], AttributeDefinition::query()->comparable()->pluck('id')->all());
    }
}

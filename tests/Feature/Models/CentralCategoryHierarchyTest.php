<?php

namespace Tests\Feature\Models;

use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralCategoryHierarchyTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_categories_have_parent_id_column(): void
    {
        $this->assertTrue(Schema::hasColumn('central_categories', 'parent_id'));
    }

    public function test_supports_parent_child_categories(): void
    {
        $parent = CentralCategory::factory()->create(['name' => 'Electronics']);
        $child = CentralCategory::factory()->create([
            'name' => 'Monitors',
            'parent_id' => $parent->id,
        ]);

        $this->assertSame($parent->id, $child->parent->id);
        $this->assertCount(1, $parent->children);
        $this->assertSame($child->id, $parent->children->first()->id);
    }

    public function test_child_category_parent_id_becomes_null_when_parent_is_deleted(): void
    {
        $parent = CentralCategory::factory()->create();
        $child = CentralCategory::factory()->create([
            'parent_id' => $parent->id,
        ]);

        $parent->delete();

        $this->assertNull($child->fresh()->parent_id);
    }

    public function test_central_category_tracks_descendant_ids(): void
    {
        $parent = CentralCategory::factory()->create();
        $child = CentralCategory::factory()->create([
            'parent_id' => $parent->id,
        ]);
        $grandchild = CentralCategory::factory()->create([
            'parent_id' => $child->id,
        ]);

        $this->assertEqualsCanonicalizing(
            [$child->id, $grandchild->id],
            $parent->descendantIds(),
        );
    }
}

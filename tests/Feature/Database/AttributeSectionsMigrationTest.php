<?php

namespace Tests\Feature\Database;

use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttributeSectionsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_attribute_sections_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('attribute_sections'));
        $this->assertTrue(Schema::hasColumns('attribute_sections', [
            'id',
            'central_category_id',
            'code',
            'name',
            'position',
            'display_style',
            'is_collapsible',
            'is_visible',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_attribute_sections_have_expected_indexes(): void
    {
        $indexes = collect(Schema::getIndexes('attribute_sections'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['central_category_id', 'code']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['central_category_id', 'position']
        ));
    }

    public function test_sections_are_structurally_flat_from_birth(): void
    {
        self::assertFalse(Schema::hasColumn('attribute_sections', 'parent_id'));
        self::assertFalse(collect(Schema::getForeignKeys('attribute_sections'))
            ->contains(fn (array $key): bool => $key['foreign_table'] === 'attribute_sections'));
    }

    public function test_category_deletion_removes_unassigned_flat_sections(): void
    {
        $category = CentralCategory::factory()->create();
        $parent = AttributeSection::factory()->for($category, 'category')->create();
        $child = AttributeSection::factory()
            ->for($category, 'category')
            ->create();

        $category->delete();

        $this->assertDatabaseMissing('attribute_sections', ['id' => $parent->id]);
        $this->assertDatabaseMissing('attribute_sections', ['id' => $child->id]);
    }
}

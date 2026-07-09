<?php

namespace Tests\Feature\Database;

use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Database\QueryException;
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
            'parent_id',
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

    public function test_attribute_section_parent_must_belong_to_same_category(): void
    {
        $parentCategory = CentralCategory::factory()->create();
        $childCategory = CentralCategory::factory()->create();
        $parent = AttributeSection::factory()->for($parentCategory, 'category')->create();

        $this->expectException(QueryException::class);

        AttributeSection::factory()
            ->for($childCategory, 'category')
            ->create(['parent_id' => $parent->id]);
    }

    public function test_category_deletion_removes_nested_attribute_sections(): void
    {
        $category = CentralCategory::factory()->create();
        $parent = AttributeSection::factory()->for($category, 'category')->create();
        $child = AttributeSection::factory()
            ->for($category, 'category')
            ->for($parent, 'parent')
            ->create();

        $category->delete();

        $this->assertDatabaseMissing('attribute_sections', ['id' => $parent->id]);
        $this->assertDatabaseMissing('attribute_sections', ['id' => $child->id]);
    }
}

<?php

namespace Tests\Feature\Database;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttributeDefinitionsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_attribute_definitions_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('attribute_definitions'));
        $this->assertTrue(Schema::hasColumns('attribute_definitions', ['id', 'code', 'name', 'data_type', 'measurement_dimension_id', 'canonical_measurement_unit_id', 'created_at', 'updated_at']));
        foreach (['central_category_id', 'attribute_section_id', 'position', 'is_required', 'is_visible', 'is_searchable', 'is_sortable', 'is_filterable', 'is_comparable', 'dimension', 'canonical_unit', 'canonical_code'] as $column) {
            $this->assertFalse(Schema::hasColumn('attribute_definitions', $column), $column);
        }
    }

    public function test_attribute_definitions_have_expected_indexes(): void
    {
        $indexes = collect(Schema::getIndexes('attribute_definitions'));
        $this->assertTrue($indexes->contains(fn (array $index): bool => $index['unique'] && $index['columns'] === ['code']));
        $this->assertFalse($indexes->contains(fn (array $index): bool => in_array('central_category_id', $index['columns'], true)));
    }

    public function test_attribute_definition_section_must_belong_to_same_category(): void
    {
        $sectionCategory = CentralCategory::factory()->create();
        $attributeCategory = CentralCategory::factory()->create();
        $section = AttributeSection::factory()->for($sectionCategory, 'category')->create();

        $this->expectException(QueryException::class);

        AttributeDefinition::factory()
            ->assignedTo($attributeCategory)
            ->create(['attribute_section_id' => $section->id]);
    }
}

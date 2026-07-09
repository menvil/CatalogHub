<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttributeDefinitionsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_attribute_definitions_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('attribute_definitions'));
        $this->assertTrue(Schema::hasColumns('attribute_definitions', [
            'id',
            'central_category_id',
            'attribute_section_id',
            'code',
            'name',
            'data_type',
            'dimension',
            'canonical_unit',
            'position',
            'is_required',
            'is_filterable',
            'is_sortable',
            'is_comparable',
            'is_visible',
            'is_searchable',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_attribute_definitions_have_expected_indexes(): void
    {
        $indexes = collect(Schema::getIndexes('attribute_definitions'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['central_category_id', 'code']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['central_category_id', 'attribute_section_id', 'position']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['central_category_id', 'is_filterable']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['central_category_id', 'is_comparable']
        ));
    }
}

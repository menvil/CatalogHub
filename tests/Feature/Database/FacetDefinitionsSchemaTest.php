<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FacetDefinitionsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_facet_definitions_table_has_expected_schema_and_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('facet_definitions'));
        $this->assertTrue(Schema::hasColumns('facet_definitions', [
            'id',
            'category_id',
            'attribute_definition_id',
            'code',
            'label_override',
            'facet_type',
            'source_type',
            'is_active',
            'is_filterable',
            'is_visible',
            'is_collapsible',
            'default_collapsed',
            'position',
            'config_json',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('facet_definitions'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['attribute_definition_id'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['code'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['category_id', 'code'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['category_id', 'position'],
        ));
    }
}

<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FacetOptionsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_facet_options_table_has_expected_schema_and_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('facet_options'));
        $this->assertTrue(Schema::hasColumns('facet_options', [
            'id',
            'facet_definition_id',
            'value',
            'label_override',
            'position',
            'is_active',
            'config_json',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('facet_options'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['facet_definition_id'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['facet_definition_id', 'value'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['facet_definition_id', 'position'],
        ));
    }
}

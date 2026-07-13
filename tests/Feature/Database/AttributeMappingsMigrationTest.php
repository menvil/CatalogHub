<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttributeMappingsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_attribute_mappings_table_with_review_fields(): void
    {
        $this->assertTrue(Schema::hasTable('attribute_mappings'));
        $this->assertTrue(Schema::hasColumns('attribute_mappings', [
            'id',
            'import_source_id',
            'category_id',
            'raw_key',
            'normalized_raw_key',
            'attribute_definition_id',
            'confidence',
            'status',
            'mapping_type',
            'notes',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_attribute_mapping_has_expected_foreign_keys_and_source_category_key(): void
    {
        $foreignKeys = collect(Schema::getForeignKeys('attribute_mappings'));
        $indexes = collect(Schema::getIndexes('attribute_mappings'));

        foreach ([
            'import_sources' => 'import_source_id',
            'central_categories' => 'category_id',
            'attribute_definitions' => 'attribute_definition_id',
        ] as $table => $column) {
            $this->assertTrue($foreignKeys->contains(
                fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]
                    && $foreignKey['foreign_table'] === $table
            ));
        }

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['import_source_id', 'category_id', 'raw_key']
        ));
    }
}

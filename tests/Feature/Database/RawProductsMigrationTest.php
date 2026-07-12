<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RawProductsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_raw_products_table_with_payload_and_pipeline_columns(): void
    {
        $this->assertTrue(Schema::hasTable('raw_products'));
        $this->assertTrue(Schema::hasColumns('raw_products', [
            'id',
            'import_batch_id',
            'import_source_id',
            'external_id',
            'source_row_number',
            'raw_title',
            'raw_brand',
            'raw_category',
            'raw_payload_json',
            'payload_hash',
            'status',
            'error_message',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_raw_products_has_source_relationships_and_lookup_indexes(): void
    {
        $foreignKeys = collect(Schema::getForeignKeys('raw_products'));
        $indexes = collect(Schema::getIndexes('raw_products'));

        foreach (['import_batches' => 'import_batch_id', 'import_sources' => 'import_source_id'] as $table => $column) {
            $this->assertTrue($foreignKeys->contains(
                fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]
                    && $foreignKey['foreign_table'] === $table
            ));
        }

        foreach (['import_batch_id', 'import_source_id', 'external_id', 'payload_hash'] as $column) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === [$column]
            ));
        }
    }
}

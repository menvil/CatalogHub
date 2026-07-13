<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class NormalizationErrorsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_normalization_errors_table_with_diagnostic_and_resolution_fields(): void
    {
        $this->assertTrue(Schema::hasTable('normalization_errors'));
        $this->assertTrue(Schema::hasColumns('normalization_errors', [
            'id',
            'import_batch_id',
            'raw_product_id',
            'normalized_product_draft_id',
            'severity',
            'code',
            'message',
            'raw_key',
            'raw_value',
            'context_json',
            'resolved_at',
            'resolved_by_user_id',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_normalization_error_has_pipeline_foreign_keys_and_filter_indexes(): void
    {
        $foreignKeys = collect(Schema::getForeignKeys('normalization_errors'));
        $indexes = collect(Schema::getIndexes('normalization_errors'));

        foreach ([
            'import_batches' => 'import_batch_id',
            'raw_products' => 'raw_product_id',
            'normalized_product_drafts' => 'normalized_product_draft_id',
            'users' => 'resolved_by_user_id',
        ] as $table => $column) {
            $this->assertTrue($foreignKeys->contains(
                fn (array $foreignKey): bool => $foreignKey['columns'] === [$column]
                    && $foreignKey['foreign_table'] === $table
            ));
        }

        foreach (['severity', 'code', 'resolved_at'] as $column) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === [$column]
            ));
        }
    }
}

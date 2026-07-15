<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExternalProductMappingsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_external_product_mappings_table_has_mapping_and_audit_fields(): void
    {
        $this->assertTrue(Schema::hasTable('external_product_mappings'));
        $this->assertTrue(Schema::hasColumns('external_product_mappings', [
            'id', 'price_source_id', 'central_product_id', 'external_product_id',
            'external_sku', 'external_url', 'external_title', 'confidence', 'status',
            'approved_at', 'approved_by_user_id', 'rejected_at', 'rejected_by_user_id',
            'notes', 'metadata', 'created_at', 'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('external_product_mappings'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['name'] === 'external_mappings_source_product_unique'
                && $index['columns'] === ['price_source_id', 'external_product_id'],
        ));

        foreach ([['price_source_id', 'external_sku'], ['central_product_id'], ['status']] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}

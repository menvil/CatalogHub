<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralChangeRequestsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_change_requests_table_supports_correction_workflow(): void
    {
        $this->assertTrue(Schema::hasTable('central_change_requests'));
        $this->assertTrue(Schema::hasColumns('central_change_requests', [
            'id',
            'site_id',
            'central_product_id',
            'entity_type',
            'entity_id',
            'field_path',
            'old_value_json',
            'proposed_value_json',
            'evidence_url',
            'evidence_note',
            'status',
            'created_by_user_id',
            'reviewed_by_user_id',
            'applied_by_user_id',
            'reviewed_at',
            'applied_at',
            'rejection_reason',
            'metadata_json',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('central_change_requests'));

        foreach ([
            ['status'],
            ['site_id', 'status'],
            ['central_product_id', 'status'],
            ['created_by_user_id'],
        ] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}

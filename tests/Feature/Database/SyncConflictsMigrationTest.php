<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SyncConflictsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_conflicts_table_tracks_manual_resolution_state(): void
    {
        $this->assertTrue(Schema::hasTable('sync_conflicts'));
        $this->assertTrue(Schema::hasColumns('sync_conflicts', [
            'id',
            'site_id',
            'central_product_id',
            'entity_type',
            'entity_id',
            'field_path',
            'central_value_json',
            'local_value_json',
            'conflict_type',
            'status',
            'resolution',
            'resolved_by_user_id',
            'resolved_at',
            'metadata_json',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('sync_conflicts'));

        foreach ([
            ['site_id', 'status'],
            ['central_product_id', 'status'],
            ['conflict_type'],
        ] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}

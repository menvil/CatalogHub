<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SyncLogsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_logs_table_tracks_sync_and_rebuild_operations(): void
    {
        $this->assertTrue(Schema::hasTable('sync_logs'));
        $this->assertTrue(Schema::hasColumns('sync_logs', [
            'id',
            'site_id',
            'central_product_id',
            'central_category_id',
            'operation',
            'status',
            'triggered_by',
            'triggered_by_user_id',
            'started_at',
            'finished_at',
            'affected_count',
            'error_message',
            'context_json',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('sync_logs'));

        foreach ([
            ['site_id', 'created_at'],
            ['status', 'created_at'],
            ['operation', 'created_at'],
            ['central_product_id'],
        ] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}

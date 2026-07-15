<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PriceSourceSyncLogsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_source_sync_logs_table_tracks_pipeline_runs(): void
    {
        $this->assertTrue(Schema::hasTable('price_source_sync_logs'));
        $this->assertTrue(Schema::hasColumns('price_source_sync_logs', [
            'id',
            'price_source_id',
            'status',
            'started_at',
            'finished_at',
            'items_fetched',
            'items_normalized',
            'items_matched',
            'items_updated',
            'error_message',
            'metadata',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('price_source_sync_logs'));

        foreach ([['price_source_id', 'status'], ['started_at']] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}

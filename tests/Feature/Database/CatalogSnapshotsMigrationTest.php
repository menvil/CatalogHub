<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CatalogSnapshotsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_snapshots_table_tracks_export_lifecycle_and_files(): void
    {
        $this->assertTrue(Schema::hasTable('catalog_snapshots'));
        $this->assertTrue(Schema::hasColumns('catalog_snapshots', [
            'id',
            'uuid',
            'status',
            'snapshot_type',
            'storage_disk',
            'storage_path',
            'files_json',
            'metadata_json',
            'started_at',
            'completed_at',
            'failed_at',
            'failure_reason',
            'created_by_user_id',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('catalog_snapshots'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['uuid'] && $index['unique'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['status', 'created_at'],
        ));
    }
}

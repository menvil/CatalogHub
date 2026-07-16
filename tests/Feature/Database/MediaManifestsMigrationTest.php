<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MediaManifestsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_manifests_table_tracks_snapshot_asset_integrity(): void
    {
        $this->assertTrue(Schema::hasTable('media_manifests'));
        $this->assertTrue(Schema::hasColumns('media_manifests', [
            'id',
            'catalog_snapshot_id',
            'media_asset_id',
            'asset_uuid',
            'original_path',
            'variants_json',
            'checksum',
            'file_size',
            'mime_type',
            'status',
            'metadata_json',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('media_manifests'));

        foreach ([['catalog_snapshot_id', 'status'], ['media_asset_id'], ['asset_uuid'], ['status']] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}

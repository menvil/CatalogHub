<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MediaAssetsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_media_assets_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('media_assets'));
        $this->assertTrue(Schema::hasColumns('media_assets', [
            'id',
            'uuid',
            'type',
            'disk',
            'original_path',
            'original_filename',
            'mime_type',
            'file_size',
            'width',
            'height',
            'checksum',
            'status',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_media_assets_uuid_is_unique_and_checksum_is_indexed(): void
    {
        $indexes = collect(Schema::getIndexes('media_assets'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['uuid']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['checksum']
        ));
    }
}

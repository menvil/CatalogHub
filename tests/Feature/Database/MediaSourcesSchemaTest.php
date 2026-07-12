<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MediaSourcesSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_media_sources_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('media_sources'));
        $this->assertTrue(Schema::hasColumns('media_sources', [
            'id',
            'media_asset_id',
            'source_type',
            'source_url',
            'source_name',
            'license_type',
            'license_url',
            'attribution',
            'metadata',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_media_sources_allows_only_one_source_record_per_asset(): void
    {
        $indexes = collect(Schema::getIndexes('media_sources'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['media_asset_id']
        ));
    }
}

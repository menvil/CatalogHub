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
}

<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MediaVariantsSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_media_variants_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('media_variants'));
        $this->assertTrue(Schema::hasColumns('media_variants', [
            'id',
            'media_asset_id',
            'variant_type',
            'disk',
            'path',
            'width',
            'height',
            'format',
            'file_size',
            'quality',
            'status',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_media_variants_has_lookup_indexes(): void
    {
        $indexes = collect(Schema::getIndexes('media_variants'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['media_asset_id', 'variant_type']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['media_asset_id', 'variant_type', 'locale', 'site_id', 'market_id']
        ));
    }
}

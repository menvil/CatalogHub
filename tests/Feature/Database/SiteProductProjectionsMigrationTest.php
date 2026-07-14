<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteProductProjectionsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_product_projections_table_has_read_model_schema_and_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('site_product_projections'));
        $this->assertTrue(Schema::hasColumns('site_product_projections', [
            'id',
            'site_id',
            'locale',
            'central_product_id',
            'central_product_version',
            'slug',
            'canonical_url',
            'title',
            'status',
            'payload_json',
            'seo_json',
            'media_json',
            'search_summary_json',
            'checksum',
            'built_at',
            'stale_at',
            'failed_at',
            'failure_reason',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('site_product_projections'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['site_id', 'locale', 'central_product_id'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['site_id', 'locale', 'slug'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['site_id', 'status'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['central_product_id'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['checksum'],
        ));
    }
}

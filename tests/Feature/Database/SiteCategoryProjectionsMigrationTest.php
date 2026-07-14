<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteCategoryProjectionsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_category_projections_table_has_read_model_schema_and_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('site_category_projections'));
        $this->assertTrue(Schema::hasColumns('site_category_projections', [
            'id',
            'site_id',
            'locale',
            'central_category_id',
            'central_category_version',
            'parent_category_id',
            'slug',
            'title',
            'status',
            'payload_json',
            'seo_json',
            'facets_json',
            'comparison_json',
            'checksum',
            'built_at',
            'stale_at',
            'failed_at',
            'failure_reason',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('site_category_projections'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['site_id', 'locale', 'central_category_id'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['site_id', 'locale', 'slug'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['site_id', 'status'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['central_category_id'],
        ));
    }
}

<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteSitemapUrlsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_sitemap_urls_table_has_generated_url_schema_and_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('site_sitemap_urls'));
        $this->assertTrue(Schema::hasColumns('site_sitemap_urls', [
            'id',
            'site_id',
            'locale',
            'url',
            'entity_type',
            'entity_id',
            'changefreq',
            'priority',
            'lastmod_at',
            'status',
            'checksum',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('site_sitemap_urls'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['site_id', 'locale', 'url'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['site_id', 'locale', 'status'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['entity_type', 'entity_id'],
        ));
    }
}

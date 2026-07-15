<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteSearchDocumentsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_search_documents_table_has_normalized_search_schema_and_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('site_search_documents'));
        $this->assertTrue(Schema::hasColumns('site_search_documents', [
            'id',
            'site_id',
            'locale',
            'document_type',
            'document_id',
            'title',
            'slug',
            'status',
            'search_text',
            'filter_values_json',
            'sort_values_json',
            'payload_json',
            'checksum',
            'built_at',
            'stale_at',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('site_search_documents'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['site_id', 'locale', 'document_type', 'document_id'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['site_id', 'locale', 'status'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['site_id', 'locale', 'document_type'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === false
                && $index['columns'] === ['checksum'],
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === false
                && $index['columns'] === ['site_id', 'min_price', 'max_price'],
        ));
    }
}

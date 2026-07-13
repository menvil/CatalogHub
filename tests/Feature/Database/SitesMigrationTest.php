<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SitesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_sites_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('sites'));
        $this->assertTrue(Schema::hasColumns('sites', [
            'id', 'market_id', 'code', 'name', 'domain', 'mode', 'default_locale',
            'status', 'settings_json', 'deleted_at', 'created_at', 'updated_at',
        ]));
    }

    public function test_sites_has_unique_code_and_domain_indexes(): void
    {
        $indexes = collect(Schema::getIndexes('sites'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['code']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['domain']
        ));
    }
}

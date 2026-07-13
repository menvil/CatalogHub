<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteLocalesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_site_locales_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('site_locales'));
        $this->assertTrue(Schema::hasColumns('site_locales', [
            'id', 'site_id', 'locale_code', 'is_default', 'is_enabled', 'position', 'created_at', 'updated_at',
        ]));
    }

    public function test_site_locale_is_unique_per_site(): void
    {
        $indexes = collect(Schema::getIndexes('site_locales'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['site_id', 'locale_code']
        ));
    }
}

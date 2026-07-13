<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteCategoriesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_site_categories_table_with_required_columns_and_unique_pair(): void
    {
        $this->assertTrue(Schema::hasTable('site_categories'));
        $this->assertTrue(Schema::hasColumns('site_categories', ['id', 'site_id', 'central_category_id', 'is_enabled', 'position', 'local_status', 'settings_json', 'created_at', 'updated_at']));

        $indexes = collect(Schema::getIndexes('site_categories'));
        $this->assertTrue($indexes->contains(fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['site_id', 'central_category_id']));
    }
}

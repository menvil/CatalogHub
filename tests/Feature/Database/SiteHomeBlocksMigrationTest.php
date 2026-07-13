<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SiteHomeBlocksMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_home_blocks_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('site_home_blocks'));
        $this->assertTrue(Schema::hasColumns('site_home_blocks', [
            'id',
            'site_id',
            'block_code',
            'position',
            'enabled',
            'config_json',
            'visibility_json',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_site_home_blocks_have_site_and_registry_foreign_keys(): void
    {
        $foreignKeys = collect(Schema::getForeignKeys('site_home_blocks'));

        $this->assertTrue($foreignKeys->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['site_id'] && $foreignKey['foreign_table'] === 'sites'
        ));
        $this->assertTrue($foreignKeys->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['block_code'] && $foreignKey['foreign_table'] === 'block_registry'
        ));
    }

    public function test_site_home_blocks_are_orderable_and_scope_unique(): void
    {
        $indexes = collect(Schema::getIndexes('site_home_blocks'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['site_id', 'block_code', 'position']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['site_id', 'position']
        ));
    }
}

<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BlockRegistryMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_block_registry_table_has_required_definition_columns(): void
    {
        $this->assertTrue(Schema::hasTable('block_registry'));
        $this->assertTrue(Schema::hasColumns('block_registry', [
            'id',
            'code',
            'name',
            'description',
            'category',
            'supported_page_types_json',
            'required_features_json',
            'config_schema_json',
            'view_component',
            'preview_image_path',
            'status',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_block_code_is_unique_and_registry_filters_are_indexed(): void
    {
        $indexes = collect(Schema::getIndexes('block_registry'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['code']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['category']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['status']
        ));
    }
}

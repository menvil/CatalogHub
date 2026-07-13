<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportSourcesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_import_sources_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('import_sources'));
        $this->assertTrue(Schema::hasColumns('import_sources', [
            'id',
            'code',
            'name',
            'type',
            'status',
            'config_json',
            'description',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_import_source_code_is_unique_and_type_and_status_are_indexed(): void
    {
        $indexes = collect(Schema::getIndexes('import_sources'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['code']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['type']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['status']
        ));
    }
}

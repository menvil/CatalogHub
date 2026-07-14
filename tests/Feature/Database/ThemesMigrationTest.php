<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ThemesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_themes_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('themes'));
        $this->assertTrue(Schema::hasColumns('themes', [
            'id',
            'code',
            'name',
            'description',
            'status',
            'version',
            'preview_image_path',
            'is_system',
            'config_json',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_theme_code_is_unique_and_status_is_indexed(): void
    {
        $indexes = collect(Schema::getIndexes('themes'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['code']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['status']
        ));
    }
}

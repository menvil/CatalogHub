<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LayoutTemplatesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_layout_templates_table_has_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('layout_templates'));
        $this->assertTrue(Schema::hasColumns('layout_templates', [
            'id',
            'theme_id',
            'page_type',
            'code',
            'name',
            'view_path',
            'slots_json',
            'config_schema_json',
            'status',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_layout_templates_are_unique_within_theme_page_and_code(): void
    {
        $indexes = collect(Schema::getIndexes('layout_templates'));
        $foreignKeys = collect(Schema::getForeignKeys('layout_templates'));
        $unique = $indexes->firstWhere('name', 'layout_templates_theme_page_code_unique');

        $this->assertNotNull($unique);
        $this->assertTrue($unique['unique']);
        $this->assertSame(['theme_id', 'page_type', 'code'], $unique['columns']);
        $this->assertTrue($foreignKeys->contains(
            fn (array $foreignKey): bool => $foreignKey['columns'] === ['theme_id'] && $foreignKey['foreign_table'] === 'themes'
        ));
    }
}

<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttributeSectionsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_attribute_sections_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('attribute_sections'));
        $this->assertTrue(Schema::hasColumns('attribute_sections', [
            'id',
            'central_category_id',
            'parent_id',
            'code',
            'name',
            'position',
            'display_style',
            'is_collapsible',
            'is_visible',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_attribute_sections_have_expected_indexes(): void
    {
        $indexes = collect(Schema::getIndexes('attribute_sections'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['central_category_id', 'code']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['central_category_id', 'position']
        ));
    }
}

<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AttributeOptionsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_attribute_options_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('attribute_options'));
        $this->assertTrue(Schema::hasColumns('attribute_options', [
            'id',
            'attribute_definition_id',
            'code',
            'label',
            'position',
            'is_visible',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_attribute_options_have_expected_indexes(): void
    {
        $indexes = collect(Schema::getIndexes('attribute_options'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['attribute_definition_id', 'code']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['attribute_definition_id', 'position']
        ));
    }
}

<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralCategoriesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_central_categories_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('central_categories'));
        $this->assertTrue(Schema::hasColumns('central_categories', [
            'id',
            'name',
            'slug',
            'position',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_central_categories_slug_is_unique_and_position_is_indexed(): void
    {
        $indexes = collect(Schema::getIndexes('central_categories'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['slug']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['position']
        ));
    }
}

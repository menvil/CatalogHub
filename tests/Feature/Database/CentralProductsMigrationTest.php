<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralProductsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_central_products_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('central_products'));
        $this->assertTrue(Schema::hasColumns('central_products', [
            'id',
            'name',
            'model',
            'slug',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_central_products_slug_is_unique_and_model_is_indexed(): void
    {
        $indexes = collect(Schema::getIndexes('central_products'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['slug']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['model']
        ));
    }
}

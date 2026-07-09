<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CentralBrandsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_central_brands_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('central_brands'));
        $this->assertTrue(Schema::hasColumns('central_brands', [
            'id',
            'name',
            'slug',
            'status',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_central_brands_slug_is_unique_and_status_is_indexed(): void
    {
        $indexes = collect(Schema::getIndexes('central_brands'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['slug']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['status']
        ));
    }
}

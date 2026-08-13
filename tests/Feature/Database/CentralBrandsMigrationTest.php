<?php

namespace Tests\Feature\Database;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
            'website_url',
            'country_code',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_central_brands_storage_indexes_match_the_domain_contract(): void
    {
        $indexes = collect(Schema::getIndexes('central_brands'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['slug']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['status']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === false && $index['columns'] === ['name']
        ));
    }

    public function test_central_brands_slug_uniqueness_is_enforced_by_the_database(): void
    {
        DB::table('central_brands')->insert([
            'name' => 'Samsung',
            'slug' => 'samsung',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('central_brands')->insert([
            'name' => 'Samsung Electronics',
            'slug' => 'samsung',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

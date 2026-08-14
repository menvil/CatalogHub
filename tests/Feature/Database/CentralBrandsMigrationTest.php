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
            'normalized_name',
            'normalized_name_hash',
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
        $columns = collect(Schema::getColumns('central_brands'))->keyBy('name');
        $indexes = collect(Schema::getIndexes('central_brands'));

        $this->assertFalse($columns['normalized_name']['nullable']);
        $this->assertFalse($columns['normalized_name_hash']['nullable']);

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['slug']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['status']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === false && $index['columns'] === ['name']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['normalized_name_hash']
        ));
    }

    public function test_central_brands_slug_uniqueness_is_enforced_by_the_database(): void
    {
        DB::table('central_brands')->insert([
            'name' => 'Samsung',
            'normalized_name' => 'samsung',
            'normalized_name_hash' => hash('sha256', 'samsung'),
            'slug' => 'samsung',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('central_brands')->insert([
            'name' => 'Samsung Electronics',
            'normalized_name' => 'samsung electronics',
            'normalized_name_hash' => hash('sha256', 'samsung electronics'),
            'slug' => 'samsung',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_central_brands_normalized_name_hash_uniqueness_is_enforced_by_the_database(): void
    {
        $identity = 'électro';
        $identityHash = hash('sha256', $identity);

        DB::table('central_brands')->insert([
            'name' => 'ÉLECTRO',
            'normalized_name' => $identity,
            'normalized_name_hash' => $identityHash,
            'slug' => 'electro-one',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('central_brands')->insert([
            'name' => 'électro',
            'normalized_name' => $identity,
            'normalized_name_hash' => $identityHash,
            'slug' => 'electro-two',
            'status' => 'draft',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

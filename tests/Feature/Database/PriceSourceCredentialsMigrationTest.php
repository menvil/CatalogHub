<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PriceSourceCredentialsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_source_credentials_table_has_required_columns_and_unique_source(): void
    {
        $this->assertTrue(Schema::hasTable('price_source_credentials'));
        $this->assertTrue(Schema::hasColumns('price_source_credentials', [
            'id',
            'price_source_id',
            'encrypted_credentials_json',
            'status',
            'last_rotated_at',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('price_source_credentials'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['price_source_id'],
        ));
    }
}

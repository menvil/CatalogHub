<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MarketsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_has_markets_table_with_required_columns(): void
    {
        $this->assertTrue(Schema::hasTable('markets'));
        $this->assertTrue(Schema::hasColumns('markets', [
            'id',
            'code',
            'name',
            'country_code',
            'currency_code',
            'default_locale',
            'timezone',
            'status',
            'config_json',
            'created_at',
            'updated_at',
        ]));
    }

    public function test_market_code_is_unique_and_status_is_indexed(): void
    {
        $indexes = collect(Schema::getIndexes('markets'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true && $index['columns'] === ['code']
        ));
        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['columns'] === ['status']
        ));
    }
}

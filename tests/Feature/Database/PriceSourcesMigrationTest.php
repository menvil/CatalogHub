<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PriceSourcesMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_sources_table_has_required_columns_and_indexes(): void
    {
        $this->assertTrue(Schema::hasTable('price_sources'));
        $this->assertTrue(Schema::hasColumns('price_sources', [
            'id',
            'market_id',
            'code',
            'name',
            'type',
            'status',
            'config_json',
            'update_frequency',
            'last_sync_at',
            'created_at',
            'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('price_sources'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['market_id', 'code'],
        ));

        foreach ([
            ['market_id', 'status'],
            ['status'],
            ['last_sync_at'],
        ] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}

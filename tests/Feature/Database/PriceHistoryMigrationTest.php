<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PriceHistoryMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_history_table_has_append_only_snapshot_fields(): void
    {
        $this->assertTrue(Schema::hasTable('price_history'));
        $this->assertTrue(Schema::hasColumns('price_history', [
            'id', 'market_offer_id', 'price', 'currency', 'availability',
            'condition', 'delivery_price', 'checked_at', 'source_snapshot_json',
            'created_at', 'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('price_history'));

        foreach ([['market_offer_id', 'checked_at'], ['checked_at']] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}

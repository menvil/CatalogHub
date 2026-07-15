<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MarketOffersMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_market_offers_table_has_current_offer_fields(): void
    {
        $this->assertTrue(Schema::hasTable('market_offers'));
        $this->assertTrue(Schema::hasColumns('market_offers', [
            'id', 'market_id', 'market_merchant_id', 'central_product_id',
            'price_source_id', 'external_product_mapping_id', 'price', 'currency',
            'original_price', 'original_currency', 'availability', 'condition',
            'delivery_price', 'delivery_time', 'url', 'last_seen_at',
            'last_checked_at', 'status', 'metadata', 'created_at', 'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('market_offers'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['market_merchant_id', 'central_product_id', 'price_source_id'],
        ));

        foreach ([['market_id', 'status'], ['central_product_id', 'status'], ['last_seen_at']] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}

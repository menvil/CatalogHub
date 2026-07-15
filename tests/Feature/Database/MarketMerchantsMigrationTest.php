<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MarketMerchantsMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_market_merchants_table_has_market_scoped_identity(): void
    {
        $this->assertTrue(Schema::hasTable('market_merchants'));
        $this->assertTrue(Schema::hasColumns('market_merchants', [
            'id', 'market_id', 'name', 'slug', 'website_url',
            'logo_media_asset_id', 'status', 'metadata', 'created_at', 'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('market_merchants'));

        $this->assertTrue($indexes->contains(
            fn (array $index): bool => $index['unique'] === true
                && $index['columns'] === ['market_id', 'slug'],
        ));

        foreach ([['market_id', 'name'], ['status']] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}

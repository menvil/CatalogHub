<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RawPriceOffersMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_raw_price_offers_table_preserves_pipeline_payloads(): void
    {
        $this->assertTrue(Schema::hasTable('raw_price_offers'));
        $this->assertTrue(Schema::hasColumns('raw_price_offers', [
            'id', 'price_source_id', 'price_source_sync_log_id',
            'external_product_id', 'external_sku', 'external_title',
            'raw_payload_json', 'normalized_payload_json', 'status',
            'error_message', 'fetched_at', 'created_at', 'updated_at',
        ]));

        $indexes = collect(Schema::getIndexes('raw_price_offers'));

        foreach ([['price_source_id', 'status'], ['price_source_sync_log_id'], ['fetched_at']] as $columns) {
            $this->assertTrue($indexes->contains(
                fn (array $index): bool => $index['columns'] === $columns,
            ));
        }
    }
}

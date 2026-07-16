<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReadinessQueryIndexesTest extends TestCase
{
    use RefreshDatabase;

    public function test_readiness_query_indexes_exist(): void
    {
        $expected = [
            'import_batches' => 'import_batches_source_status_created_idx',
            'central_change_requests' => 'change_requests_status_created_idx',
            'site_products' => 'site_products_site_sync_status_idx',
            'market_offers' => 'market_offers_site_product_status_idx',
            'price_source_sync_logs' => 'price_sync_logs_source_status_finished_idx',
        ];

        foreach ($expected as $table => $index) {
            $names = collect(Schema::getIndexes($table))->pluck('name');

            $this->assertTrue($names->contains($index), "Missing {$index} on {$table}.");
        }
    }
}

<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OfferClicksMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_offer_clicks_table_has_the_tracking_columns(): void
    {
        $this->assertTrue(Schema::hasColumns('offer_clicks', [
            'id', 'site_id', 'market_offer_id', 'central_product_id', 'merchant_id',
            'user_id', 'session_id', 'ip_hash', 'user_agent_hash', 'clicked_at',
        ]));
    }
}

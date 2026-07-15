<?php

namespace Tests\Feature\Database;

use App\Models\MarketOffer;
use App\Models\OfferClick;
use App\Models\Site;
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

    public function test_offer_clicks_are_deleted_with_their_site_or_offer(): void
    {
        $site = Site::factory()->create();
        $offer = MarketOffer::factory()->create();
        OfferClick::query()->create([
            'site_id' => $site->id,
            'market_offer_id' => $offer->id,
            'clicked_at' => now(),
        ]);

        $site->forceDelete();

        $this->assertDatabaseCount('offer_clicks', 0);

        $otherSite = Site::factory()->create();
        $otherOffer = MarketOffer::factory()->create();
        OfferClick::query()->create([
            'site_id' => $otherSite->id,
            'market_offer_id' => $otherOffer->id,
            'clicked_at' => now(),
        ]);

        $otherOffer->delete();

        $this->assertDatabaseCount('offer_clicks', 0);
    }
}

<?php

namespace Tests\Feature\Jobs\Pricing;

use App\Jobs\Pricing\StorePriceHistoryJob;
use App\Models\MarketOffer;
use App\Models\PriceHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorePriceHistoryJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_first_snapshot_and_skips_identical_duplicate(): void
    {
        $offer = MarketOffer::factory()->create([
            'price' => 249.99,
            'currency' => 'EUR',
            'last_checked_at' => now(),
        ]);

        StorePriceHistoryJob::dispatchSync($offer->id);
        StorePriceHistoryJob::dispatchSync($offer->id);

        $history = PriceHistory::query()->where('market_offer_id', $offer->id)->sole();
        $this->assertSame('249.99', $history->price);
        $this->assertSame('EUR', $history->currency);
        $this->assertNotNull($history->checked_at);
    }

    public function test_creates_new_snapshot_when_offer_value_changes(): void
    {
        $offer = MarketOffer::factory()->create(['price' => 249.99]);
        StorePriceHistoryJob::dispatchSync($offer->id);

        $offer->update(['price' => 239.99, 'last_checked_at' => now()->addMinute()]);
        StorePriceHistoryJob::dispatchSync($offer->id);

        $this->assertSame(2, PriceHistory::query()->where('market_offer_id', $offer->id)->count());
        $this->assertSame('239.99', PriceHistory::query()->latest('id')->value('price'));
    }

    public function test_force_flag_writes_scheduled_snapshot_even_when_identical(): void
    {
        $offer = MarketOffer::factory()->create();
        StorePriceHistoryJob::dispatchSync($offer->id);
        StorePriceHistoryJob::dispatchSync($offer->id, true);

        $this->assertSame(2, PriceHistory::query()->where('market_offer_id', $offer->id)->count());
    }
}

<?php

namespace Tests\Feature\Models;

use App\Enums\OfferAvailability;
use App\Enums\OfferCondition;
use App\Models\MarketOffer;
use App\Models\PriceHistory;
use Carbon\CarbonInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_price_history_with_casts_and_offer(): void
    {
        $history = PriceHistory::factory()->create([
            'price' => 249.99,
            'delivery_price' => 4.5,
            'availability' => OfferAvailability::InStock,
            'condition' => OfferCondition::New,
            'source_snapshot_json' => ['source' => 'manual'],
        ]);

        $this->assertInstanceOf(MarketOffer::class, $history->marketOffer);
        $this->assertSame('249.99', $history->price);
        $this->assertSame('4.50', $history->delivery_price);
        $this->assertSame(OfferAvailability::InStock, $history->availability);
        $this->assertSame(OfferCondition::New, $history->condition);
        $this->assertSame(['source' => 'manual'], $history->source_snapshot_json);
        $this->assertInstanceOf(CarbonInterface::class, $history->checked_at);
    }
}

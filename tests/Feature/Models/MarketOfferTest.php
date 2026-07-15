<?php

namespace Tests\Feature\Models;

use App\Enums\MarketOfferStatus;
use App\Enums\OfferAvailability;
use App\Enums\OfferCondition;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Market;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketOfferTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_market_offer_with_casts_and_relationships(): void
    {
        $offer = MarketOffer::factory()->create([
            'price' => 249.99,
            'currency' => 'EUR',
            'delivery_price' => 9.5,
            'availability' => OfferAvailability::InStock,
            'condition' => OfferCondition::New,
            'status' => MarketOfferStatus::Active,
            'metadata' => ['feed' => 'daily'],
        ]);

        $this->assertSame('249.99', $offer->price);
        $this->assertSame('9.50', $offer->delivery_price);
        $this->assertSame(OfferAvailability::InStock, $offer->availability);
        $this->assertSame(OfferCondition::New, $offer->condition);
        $this->assertSame(MarketOfferStatus::Active, $offer->status);
        $this->assertSame(['feed' => 'daily'], $offer->metadata);
        $this->assertInstanceOf(Market::class, $offer->market);
        $this->assertInstanceOf(MarketMerchant::class, $offer->merchant);
        $this->assertInstanceOf(CentralProduct::class, $offer->centralProduct);
        $this->assertInstanceOf(PriceSource::class, $offer->priceSource);
        $this->assertTrue(method_exists($offer, 'priceHistory'));
    }
}

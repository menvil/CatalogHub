<?php

namespace Tests\Unit\Pricing;

use App\Enums\MarketOfferStatus;
use App\Enums\OfferAvailability;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\Site;
use App\Services\Pricing\BestOfferResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BestOfferResolverTest extends TestCase
{
    use RefreshDatabase;

    private Site $site;

    private CentralProduct $product;

    private PriceSource $source;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-07-15 12:00:00 UTC');
        $this->site = Site::factory()->create();
        $this->product = CentralProduct::factory()->create();
        $this->source = PriceSource::factory()->active()->create(['market_id' => $this->site->market_id]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_selects_the_lowest_fresh_in_stock_total_price(): void
    {
        $baseCheaper = $this->offer('100.00', '20.00');
        $best = $this->offer('110.00', '0.00');
        $this->offer('80.00', '0.00', OfferAvailability::OutOfStock);

        $resolved = app(BestOfferResolver::class)->resolve($this->site, $this->product);

        $this->assertNotSame($baseCheaper->id, $resolved?->id);
        $this->assertSame($best->id, $resolved?->id);
    }

    public function test_it_ignores_expired_offers_when_a_fresh_offer_exists(): void
    {
        $this->offer('50.00', '0.00', checkedAt: now()->subHours(49));
        $fresh = $this->offer('100.00', null, checkedAt: now()->subHour());

        $resolved = app(BestOfferResolver::class)->resolve($this->site, $this->product);

        $this->assertSame($fresh->id, $resolved?->id);
    }

    public function test_it_returns_null_when_there_are_no_valid_offers(): void
    {
        $this->offer('50.00', null, status: MarketOfferStatus::Hidden);

        $this->assertNull(app(BestOfferResolver::class)->resolve($this->site, $this->product));
    }

    public function test_it_returns_null_when_all_in_stock_offers_are_expired(): void
    {
        $this->offer('50.00', null, checkedAt: now()->subHours(49));

        $this->assertNull(app(BestOfferResolver::class)->resolve($this->site, $this->product));
    }

    private function offer(
        string $price,
        ?string $deliveryPrice,
        OfferAvailability $availability = OfferAvailability::InStock,
        ?\DateTimeInterface $checkedAt = null,
        MarketOfferStatus $status = MarketOfferStatus::Active,
    ): MarketOffer {
        return MarketOffer::factory()->create([
            'market_id' => $this->site->market_id,
            'market_merchant_id' => MarketMerchant::factory()->create([
                'market_id' => $this->site->market_id,
            ]),
            'central_product_id' => $this->product->id,
            'price_source_id' => $this->source->id,
            'price' => $price,
            'delivery_price' => $deliveryPrice,
            'currency' => $this->site->market->currency_code,
            'availability' => $availability,
            'last_checked_at' => $checkedAt ?? now()->subHour(),
            'status' => $status,
        ]);
    }
}

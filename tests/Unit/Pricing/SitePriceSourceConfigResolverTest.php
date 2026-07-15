<?php

namespace Tests\Unit\Pricing;

use App\Enums\PriceFreshnessStatus;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\Site;
use App\Services\Pricing\PriceFreshnessCalculator;
use App\Services\Pricing\SitePriceSourceConfigResolver;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePriceSourceConfigResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_site_and_market_specific_source_config(): void
    {
        $site = Site::factory()->create();
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $site->priceSources()->attach($source, [
            'enabled' => true,
            'priority' => 10,
            'config_json' => [
                'freshness' => ['fresh_hours' => 2, 'stale_hours' => 8, 'expired_hours' => 16],
                'allow_default_market_currency' => false,
                'include_out_of_stock' => false,
            ],
        ]);

        $config = app(SitePriceSourceConfigResolver::class)->resolve($site, $source);

        $this->assertTrue($config->enabled);
        $this->assertSame(10, $config->priority);
        $this->assertSame(2, $config->freshHours);
        $this->assertSame(8, $config->staleHours);
        $this->assertSame(16, $config->expiredHours);
        $this->assertFalse($config->allowDefaultMarketCurrency);
        $this->assertFalse($config->includeOutOfStock);
    }

    public function test_it_falls_back_to_safe_defaults_when_source_config_is_missing(): void
    {
        config()->set('pricing.freshness', [
            'fresh_hours' => 6,
            'stale_hours' => 24,
            'expired_hours' => 48,
        ]);
        $site = Site::factory()->create();
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);

        $config = app(SitePriceSourceConfigResolver::class)->resolve($site, $source);

        $this->assertTrue($config->enabled);
        $this->assertNull($config->priority);
        $this->assertSame(6, $config->freshHours);
        $this->assertSame(24, $config->staleHours);
        $this->assertSame(48, $config->expiredHours);
        $this->assertTrue($config->allowDefaultMarketCurrency);
        $this->assertTrue($config->includeOutOfStock);
    }

    public function test_it_disables_a_source_from_an_unrelated_market(): void
    {
        $site = Site::factory()->create();
        $source = PriceSource::factory()->active()->create();

        $config = app(SitePriceSourceConfigResolver::class)->resolve($site, $source);

        $this->assertFalse($config->enabled);
    }

    public function test_invalid_freshness_override_falls_back_to_global_thresholds(): void
    {
        $site = Site::factory()->create();
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $site->priceSources()->attach($source, [
            'enabled' => true,
            'config_json' => [
                'freshness' => ['fresh_hours' => 20, 'stale_hours' => 10, 'expired_hours' => 5],
            ],
        ]);

        $config = app(SitePriceSourceConfigResolver::class)->resolve($site, $source);

        $this->assertSame(6, $config->freshHours);
        $this->assertSame(24, $config->staleHours);
        $this->assertSame(48, $config->expiredHours);
    }

    public function test_price_freshness_calculator_uses_the_site_source_override(): void
    {
        $now = CarbonImmutable::parse('2026-07-15 12:00:00 UTC');
        $site = Site::factory()->create();
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $site->priceSources()->attach($source, [
            'enabled' => true,
            'config_json' => [
                'freshness' => ['fresh_hours' => 1, 'stale_hours' => 2, 'expired_hours' => 3],
            ],
        ]);
        $merchant = MarketMerchant::factory()->create(['market_id' => $site->market_id]);
        $offer = MarketOffer::factory()->create([
            'market_id' => $site->market_id,
            'market_merchant_id' => $merchant->id,
            'price_source_id' => $source->id,
            'last_checked_at' => $now->subHours(3),
        ]);

        $status = app(PriceFreshnessCalculator::class)->calculate($offer, $now, $site);

        $this->assertSame(PriceFreshnessStatus::Expired, $status);
    }
}

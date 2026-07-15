<?php

namespace Tests\Feature\ViewComponents;

use App\Enums\PriceFreshnessStatus;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class OfferCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_a_compact_offer_card_with_fallbacks_and_internal_action(): void
    {
        $merchant = MarketMerchant::factory()->create([
            'name' => 'Example Merchant',
            'logo_media_asset_id' => null,
        ]);
        $offer = MarketOffer::factory()->create([
            'market_id' => $merchant->market_id,
            'market_merchant_id' => $merchant->id,
            'price' => '249.99',
            'currency' => 'EUR',
            'delivery_price' => null,
            'url' => 'https://merchant.example/private-offer',
        ]);
        $offer->load('merchant.logoMediaAsset');
        $actionUrl = '/offers/'.$offer->id.'/go';

        $html = Blade::render(
            '<x-public.offer-card :offer="$offer" :freshness="$freshness" :action-url="$actionUrl" locale="en" />',
            ['offer' => $offer, 'freshness' => PriceFreshnessStatus::Fresh, 'actionUrl' => $actionUrl],
        );

        $this->assertStringContainsString('data-offer-card', $html);
        $this->assertStringContainsString('Example Merchant', $html);
        $this->assertStringContainsString('249.99', $html);
        $this->assertStringContainsString('data-merchant-logo-fallback', $html);
        $this->assertStringContainsString('Delivery details unavailable', $html);
        $this->assertStringContainsString('href="'.$actionUrl.'"', $html);
        $this->assertStringNotContainsString((string) $offer->url, $html);
    }

    public function test_it_disables_the_action_until_a_tracked_url_is_available(): void
    {
        $offer = MarketOffer::factory()->create();
        $offer->load('merchant.logoMediaAsset');

        $html = Blade::render(
            '<x-public.offer-card :offer="$offer" freshness="unknown" locale="en" />',
            compact('offer'),
        );

        $this->assertStringContainsString('aria-disabled="true"', $html);
        $this->assertStringNotContainsString((string) $offer->url, $html);
    }
}

<?php

namespace Tests\Feature\Public;

use App\Enums\MarketOfferStatus;
use App\Enums\SiteStatus;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\OfferClick;
use App\Models\PriceSource;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferClickTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_an_offer_click_before_redirecting_to_the_external_url(): void
    {
        [$site, $offer] = $this->trackableOffer();

        $this->withHeader('User-Agent', 'CatalogHub test browser')
            ->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->get('http://'.$site->domain.'/offers/'.$offer->id.'/go')
            ->assertRedirect((string) $offer->url);

        $click = OfferClick::query()->sole();

        $this->assertTrue($click->site->is($site));
        $this->assertTrue($click->offer->is($offer));
        $this->assertSame($offer->central_product_id, $click->central_product_id);
        $this->assertSame($offer->market_merchant_id, $click->merchant_id);
        $this->assertNotNull($click->session_id);
        $this->assertSame(64, strlen((string) $click->ip_hash));
        $this->assertSame(64, strlen((string) $click->user_agent_hash));
        $this->assertSame(now()->toDateString(), $click->clicked_at->toDateString());
    }

    public function test_it_does_not_redirect_or_record_an_inactive_offer(): void
    {
        [$site, $offer] = $this->trackableOffer([
            'status' => MarketOfferStatus::Hidden,
        ]);

        $this->get('http://'.$site->domain.'/offers/'.$offer->id.'/go')
            ->assertNotFound();

        $this->assertDatabaseCount('offer_clicks', 0);
    }

    public function test_it_does_not_redirect_an_offer_without_a_safe_external_url(): void
    {
        [$site, $offer] = $this->trackableOffer(['url' => 'javascript:alert(1)']);

        $this->get('http://'.$site->domain.'/offers/'.$offer->id.'/go')
            ->assertNotFound();

        $this->assertDatabaseCount('offer_clicks', 0);
    }

    /** @param array<string, mixed> $attributes */
    private function trackableOffer(array $attributes = []): array
    {
        $site = Site::factory()->create([
            'domain' => 'tracking.test',
            'status' => SiteStatus::Active,
        ]);
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $merchant = MarketMerchant::factory()->create(['market_id' => $site->market_id]);
        $offer = MarketOffer::factory()->create([
            'market_id' => $site->market_id,
            'market_merchant_id' => $merchant->id,
            'price_source_id' => $source->id,
            'currency' => $site->market->currency_code,
            'url' => 'https://merchant.example/product/123',
            ...$attributes,
        ]);

        return [$site, $offer];
    }
}

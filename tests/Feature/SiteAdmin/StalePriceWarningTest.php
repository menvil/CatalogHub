<?php

namespace Tests\Feature\SiteAdmin;

use App\Enums\MarketOfferStatus;
use App\Enums\PriceSourceSyncStatus;
use App\Filament\Resources\SiteResource\Pages\SiteDashboard;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\Site;
use App\Models\User;
use App\Services\Pricing\StalePriceWarningBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StalePriceWarningTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_site_scoped_stale_and_expired_price_counts(): void
    {
        $site = Site::factory()->create();
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $site->priceSources()->attach($source, ['enabled' => true]);
        PriceSourceSyncLog::factory()->create([
            'price_source_id' => $source->id,
            'status' => PriceSourceSyncStatus::Completed,
            'finished_at' => now()->subHour(),
        ]);
        $this->offer($site, $source, now()->subHours(25));
        $this->offer($site, $source, now()->subHours(50));
        $disabled = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $site->priceSources()->attach($disabled, ['enabled' => false]);
        $this->offer($site, $disabled, now()->subHours(50));

        $warning = app(StalePriceWarningBuilder::class)->build($site);

        $this->assertTrue($warning->hasWarning());
        $this->assertSame(1, $warning->staleOffersCount);
        $this->assertSame(1, $warning->expiredOffersCount);
        $this->assertSame(2, $warning->affectedProductsCount);
        $this->assertNotNull($warning->lastSuccessfulUpdateAt);
    }

    public function test_site_dashboard_shows_and_links_the_stale_price_warning(): void
    {
        $site = Site::factory()->create();
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $site->priceSources()->attach($source, ['enabled' => true]);
        $this->offer($site, $source, now()->subHours(50), MarketOfferStatus::Expired);

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(SiteDashboard::getUrl(['record' => $site]))
            ->assertOk()
            ->assertSee('Price data needs attention')
            ->assertSee('Expired offers: 1')
            ->assertSee('Review stale prices')
            ->assertSee('freshness=stale', false);
    }

    public function test_site_dashboard_hides_the_warning_when_prices_are_fresh(): void
    {
        $site = Site::factory()->create();
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $this->offer($site, $source, now()->subHour());

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(SiteDashboard::getUrl(['record' => $site]))
            ->assertOk()
            ->assertDontSee('Price data needs attention');
    }

    private function offer(
        Site $site,
        PriceSource $source,
        \DateTimeInterface $checkedAt,
        MarketOfferStatus $status = MarketOfferStatus::Active,
    ): void {
        $merchant = MarketMerchant::factory()->create(['market_id' => $site->market_id]);
        MarketOffer::factory()->create([
            'market_id' => $site->market_id,
            'market_merchant_id' => $merchant->id,
            'central_product_id' => CentralProduct::factory()->create()->id,
            'price_source_id' => $source->id,
            'currency' => $site->market->currency_code,
            'last_checked_at' => $checkedAt,
            'status' => $status,
        ]);
    }
}

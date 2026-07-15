<?php

namespace Tests\Feature\SiteAdmin;

use App\Enums\PriceSourceSyncStatus;
use App\Filament\Resources\SiteResource;
use App\Filament\Resources\SiteResource\Pages\OfferProviderPreview;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\User;
use App\Services\Pricing\SiteOfferProviderPreviewBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferProviderPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_a_site_scoped_offer_provider_preview(): void
    {
        [$site, $source, $product] = $this->scenario();

        $preview = app(SiteOfferProviderPreviewBuilder::class)->build($site);

        $this->assertSame('normalized', $preview->providerMode);
        $this->assertSame([$source->name], array_column($preview->enabledSources, 'name'));
        $this->assertSame($product->name, $preview->sampleProductName);
        $this->assertSame('Preview Merchant', $preview->sampleOffers[0]['merchant']);
        $this->assertNotNull($preview->lastSuccessfulSyncAt);
        $this->assertFalse($preview->widgetEnabled);
    }

    public function test_site_admin_can_open_the_preview_page_for_their_site(): void
    {
        [$site, $source] = $this->scenario();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(OfferProviderPreview::getUrl(['record' => $site]))
            ->assertOk()
            ->assertSee('Site Price Provider Preview')
            ->assertSee('Normalized offers')
            ->assertSee($source->name)
            ->assertSee('Preview Merchant')
            ->assertSee('Widget fallback disabled');

        $this->assertArrayHasKey('pricing-preview', SiteResource::getPages());
    }

    /** @return array{Site, PriceSource, CentralProduct} */
    private function scenario(): array
    {
        $site = Site::factory()->create([
            'settings_json' => ['pricing' => ['provider_mode' => 'normalized']],
        ]);
        $source = PriceSource::factory()->active()->create([
            'market_id' => $site->market_id,
            'name' => 'Site Market Feed',
        ]);
        PriceSource::factory()->create([
            'market_id' => $site->market_id,
            'name' => 'Disabled Feed',
        ]);
        PriceSource::factory()->active()->create(['name' => 'Other Market Feed']);
        PriceSourceSyncLog::factory()->create([
            'price_source_id' => $source->id,
            'status' => PriceSourceSyncStatus::Completed,
            'started_at' => now()->subMinutes(5),
            'finished_at' => now(),
        ]);
        $product = CentralProduct::factory()->create(['name' => 'Preview Product']);
        SiteProduct::query()->create([
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'visibility' => 'visible',
        ]);
        $merchant = MarketMerchant::factory()->create([
            'market_id' => $site->market_id,
            'name' => 'Preview Merchant',
        ]);
        MarketOffer::factory()->create([
            'market_id' => $site->market_id,
            'market_merchant_id' => $merchant->id,
            'central_product_id' => $product->id,
            'price_source_id' => $source->id,
            'currency' => $site->market->currency_code,
            'price' => '199.99',
        ]);

        return [$site, $source, $product];
    }
}

<?php

namespace Tests\Feature\SiteAdmin;

use App\Enums\OfferAvailability;
use App\Enums\PriceSourceStatus;
use App\Filament\Resources\SiteResource\Pages\EditSite;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\Site;
use App\Models\User;
use App\Services\Pricing\ProductPriceSummaryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class SitePriceSourceSelectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_admin_can_enable_and_disable_market_price_sources(): void
    {
        $site = Site::factory()->create();
        $enabled = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $disabled = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $unrelated = PriceSource::factory()->active()->create();

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(EditSite::class, ['record' => $site->getRouteKey()])
            ->fillForm(['enabled_price_source_ids' => [$enabled->id]])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('site_price_sources', [
            'site_id' => $site->id,
            'price_source_id' => $enabled->id,
            'enabled' => true,
        ]);
        $this->assertDatabaseHas('site_price_sources', [
            'site_id' => $site->id,
            'price_source_id' => $disabled->id,
            'enabled' => false,
        ]);
        $this->assertDatabaseMissing('site_price_sources', [
            'site_id' => $site->id,
            'price_source_id' => $unrelated->id,
        ]);
    }

    public function test_site_admin_source_options_exclude_inactive_market_sources(): void
    {
        $site = Site::factory()->create();
        $active = PriceSource::factory()->active()->create([
            'market_id' => $site->market_id,
            'name' => 'Selectable Active Feed',
        ]);
        $inactive = PriceSource::factory()->create([
            'market_id' => $site->market_id,
            'name' => 'Hidden Inactive Feed',
            'status' => PriceSourceStatus::Inactive,
        ]);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(EditSite::class, ['record' => $site->getRouteKey()])
            ->assertSee($active->name)
            ->assertDontSee($inactive->name);
    }

    public function test_disabled_site_price_source_does_not_affect_price_summary(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $merchant = MarketMerchant::factory()->create(['market_id' => $site->market_id]);
        $enabled = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $disabled = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $site->priceSources()->attach([
            $enabled->id => ['enabled' => true],
            $disabled->id => ['enabled' => false],
        ]);
        $this->offer($site, $product, $merchant, $enabled, '299.99');
        $this->offer($site, $product, $merchant, $disabled, '99.99');

        $summary = app(ProductPriceSummaryBuilder::class)->build($site->id, $product->id);

        $this->assertSame('299.99', $summary->minPrice);
        $this->assertSame('299.99', $summary->maxPrice);
        $this->assertSame(1, $summary->offersCount);
    }

    public function test_sites_without_an_explicit_selection_keep_active_market_sources_enabled(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $merchant = MarketMerchant::factory()->create(['market_id' => $site->market_id]);
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $this->offer($site, $product, $merchant, $source, '199.99');

        $summary = app(ProductPriceSummaryBuilder::class)->build($site->id, $product->id);

        $this->assertSame('199.99', $summary->minPrice);
        $this->assertSame(1, $summary->offersCount);
    }

    public function test_price_summary_loads_valid_offers_once(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $merchant = MarketMerchant::factory()->create(['market_id' => $site->market_id]);
        $otherMerchant = MarketMerchant::factory()->create(['market_id' => $site->market_id]);
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $this->offer($site, $product, $merchant, $source, '199.99');
        $this->offer($site, $product, $otherMerchant, $source, '249.99');
        DB::flushQueryLog();
        DB::enableQueryLog();

        app(ProductPriceSummaryBuilder::class)->build($site->id, $product->id);

        $offerQueries = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'market_offers'));
        DB::disableQueryLog();
        $this->assertCount(1, $offerQueries);
    }

    public function test_price_summary_respects_source_out_of_stock_config(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $merchant = MarketMerchant::factory()->create(['market_id' => $site->market_id]);
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $site->priceSources()->attach($source, [
            'enabled' => true,
            'config_json' => ['include_out_of_stock' => false],
        ]);
        MarketOffer::factory()->create([
            'market_id' => $site->market_id,
            'market_merchant_id' => $merchant->id,
            'central_product_id' => $product->id,
            'price_source_id' => $source->id,
            'currency' => $site->market->currency_code,
            'availability' => OfferAvailability::OutOfStock,
            'price' => '99.99',
        ]);

        $summary = app(ProductPriceSummaryBuilder::class)->build($site->id, $product->id);

        $this->assertNull($summary->minPrice);
        $this->assertSame(0, $summary->offersCount);
    }

    public function test_site_admin_can_save_source_priority_and_market_config_fields(): void
    {
        $site = Site::factory()->create();
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $site->priceSources()->attach($source, ['enabled' => true]);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(EditSite::class, ['record' => $site->getRouteKey()])
            ->fillForm(['price_source_configs' => [[
                'price_source_id' => $source->id,
                'source_name' => $source->name,
                'priority' => 5,
                'fresh_hours' => 3,
                'stale_hours' => 12,
                'expired_hours' => 36,
                'allow_default_market_currency' => false,
                'include_out_of_stock' => false,
            ]]])
            ->call('save')
            ->assertHasNoFormErrors();

        $pivot = $site->priceSources()->whereKey($source->id)->firstOrFail()->pivot;
        $this->assertSame(5, $pivot->priority);
        $this->assertSame([
            'freshness' => ['fresh_hours' => 3, 'stale_hours' => 12, 'expired_hours' => 36],
            'allow_default_market_currency' => false,
            'include_out_of_stock' => false,
        ], $pivot->config_json);
    }

    private function offer(
        Site $site,
        CentralProduct $product,
        MarketMerchant $merchant,
        PriceSource $source,
        string $price,
    ): void {
        MarketOffer::factory()->create([
            'market_id' => $site->market_id,
            'market_merchant_id' => $merchant->id,
            'central_product_id' => $product->id,
            'price_source_id' => $source->id,
            'currency' => $site->market->currency_code,
            'price' => $price,
        ]);
    }
}

<?php

namespace Tests\Feature\SiteAdmin;

use App\Enums\PriceFreshnessStatus;
use App\Filament\Resources\SiteResource;
use App\Filament\Resources\SiteResource\Pages\CheapestProductsReport;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\SiteSearchDocument;
use App\Models\User;
use App\Services\Pricing\CheapestProductsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheapestProductsReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_lists_products_ordered_by_min_price_and_excludes_products_without_price(): void
    {
        [$site, $cheap, $expensive] = $this->scenario();

        $results = app(CheapestProductsQuery::class)->forSite($site)->get();

        $this->assertEquals([$cheap->id, $expensive->id], $results->pluck('document_id')->all());
        $this->assertSame('Cheap Merchant', $results->first()->getAttribute('best_merchant'));
    }

    public function test_report_query_supports_category_merchant_freshness_and_stock_filters(): void
    {
        [$site, $cheap, $expensive, $merchant, $category] = $this->scenario();

        $results = app(CheapestProductsQuery::class)->forSite(
            $site,
            categoryId: $category->id,
            merchantId: $merchant->id,
            freshness: PriceFreshnessStatus::Fresh,
            inStockOnly: true,
        )->get();

        $this->assertEquals([$cheap->id], $results->pluck('document_id')->all());
        $this->assertFalse($results->contains('document_id', $expensive->id));
    }

    public function test_site_admin_can_open_the_cheapest_products_report(): void
    {
        [$site, $cheap, $expensive] = $this->scenario();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(CheapestProductsReport::getUrl(['record' => $site]))
            ->assertOk()
            ->assertSee('Cheapest Products')
            ->assertSee($cheap->name)
            ->assertSee($expensive->name)
            ->assertSee('Cheap Merchant')
            ->assertSee('Updated recently');

        $this->assertArrayHasKey('cheapest-products', SiteResource::getPages());
    }

    public function test_formatted_price_uses_the_missing_value_marker_for_a_null_price(): void
    {
        $site = Site::factory()->create();
        $document = SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'min_price' => null,
        ]);
        $page = $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(CheapestProductsReport::getUrl(['record' => $site]));

        $page->assertOk();
        $this->assertSame('—', app(CheapestProductsReport::class)->formattedPrice($document));
    }

    /** @return array{Site, CentralProduct, CentralProduct, MarketMerchant, CentralCategory} */
    private function scenario(): array
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $category = CentralCategory::factory()->create();
        $otherCategory = CentralCategory::factory()->create();
        $cheap = CentralProduct::factory()->create([
            'name' => 'Cheap Product',
            'central_category_id' => $category->id,
        ]);
        $expensive = CentralProduct::factory()->create([
            'name' => 'Expensive Product',
            'central_category_id' => $otherCategory->id,
        ]);
        $withoutPrice = CentralProduct::factory()->create(['name' => 'No Price Product']);
        foreach ([$cheap, $expensive, $withoutPrice] as $product) {
            SiteProduct::query()->create([
                'site_id' => $site->id,
                'central_product_id' => $product->id,
                'visibility' => 'visible',
            ]);
        }
        SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'locale' => 'en',
            'document_id' => $cheap->id,
            'min_price' => '100.00',
            'max_price' => '120.00',
            'offers_count' => 1,
            'in_stock' => true,
            'last_price_update_at' => now()->subHour(),
        ]);
        SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'locale' => 'en',
            'document_id' => $expensive->id,
            'min_price' => '200.00',
            'max_price' => '250.00',
            'offers_count' => 1,
            'in_stock' => true,
            'last_price_update_at' => now()->subHours(25),
        ]);
        SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'locale' => 'en',
            'document_id' => $withoutPrice->id,
            'min_price' => null,
        ]);
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $site->priceSources()->attach($source, ['enabled' => true]);
        $merchant = MarketMerchant::factory()->create([
            'market_id' => $site->market_id,
            'name' => 'Cheap Merchant',
        ]);
        MarketOffer::factory()->create([
            'market_id' => $site->market_id,
            'market_merchant_id' => $merchant->id,
            'central_product_id' => $cheap->id,
            'price_source_id' => $source->id,
            'currency' => $site->market->currency_code,
            'price' => '100.00',
        ]);
        MarketOffer::factory()->create([
            'market_id' => $site->market_id,
            'market_merchant_id' => MarketMerchant::factory()->create(['market_id' => $site->market_id]),
            'central_product_id' => $expensive->id,
            'price_source_id' => $source->id,
            'currency' => $site->market->currency_code,
            'price' => '200.00',
        ]);

        return [$site, $cheap, $expensive, $merchant, $category];
    }
}

<?php

namespace Tests\Feature\SiteAdmin;

use App\Filament\Resources\SiteResource;
use App\Filament\Resources\SiteResource\Pages\OffersCoverageDashboard;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\SiteSearchDocument;
use App\Models\User;
use App\Services\Pricing\OfferCoverageDashboardBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OffersCoverageDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_calculates_overall_category_and_source_coverage_for_a_site(): void
    {
        [$site, $source] = $this->scenario();

        $dashboard = app(OfferCoverageDashboardBuilder::class)->build($site);

        $this->assertSame(3, $dashboard->totalVisibleProducts);
        $this->assertSame(2, $dashboard->productsWithOffers);
        $this->assertSame(1, $dashboard->productsWithoutOffers);
        $this->assertSame(66.67, $dashboard->coveragePercent);
        $this->assertSame([
            ['name' => 'Category A', 'total' => 2, 'covered' => 1, 'percent' => 50.0],
            ['name' => 'Category B', 'total' => 1, 'covered' => 1, 'percent' => 100.0],
        ], $dashboard->categoryCoverage);
        $this->assertSame($source->name, $dashboard->sourceCoverage[0]['name']);
        $this->assertSame(2, $dashboard->sourceCoverage[0]['covered']);
        $this->assertSame(66.67, $dashboard->sourceCoverage[0]['percent']);
    }

    public function test_site_admin_can_open_the_offer_coverage_dashboard(): void
    {
        [$site, $source] = $this->scenario();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(OffersCoverageDashboard::getUrl(['record' => $site]))
            ->assertOk()
            ->assertSee('Offers Coverage Dashboard')
            ->assertSee('66.67%')
            ->assertSee('Category A')
            ->assertSee($source->name)
            ->assertSee('Products without offers');

        $this->assertArrayHasKey('offers-coverage', SiteResource::getPages());
    }

    public function test_products_without_a_category_are_reported_as_uncategorised(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $product = CentralProduct::factory()->create(['central_category_id' => null]);
        SiteProduct::query()->create([
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'visibility' => 'visible',
        ]);

        $dashboard = app(OfferCoverageDashboardBuilder::class)->build($site);

        $this->assertSame([
            ['name' => 'Uncategorised', 'total' => 1, 'covered' => 0, 'percent' => 0.0],
        ], $dashboard->categoryCoverage);
    }

    /** @return array{Site, PriceSource} */
    private function scenario(): array
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $categoryA = CentralCategory::factory()->create(['name' => 'Category A']);
        $categoryB = CentralCategory::factory()->create(['name' => 'Category B']);
        $first = CentralProduct::factory()->create(['central_category_id' => $categoryA->id]);
        $second = CentralProduct::factory()->create(['central_category_id' => $categoryA->id]);
        $third = CentralProduct::factory()->create(['central_category_id' => $categoryB->id]);

        foreach ([$first, $second, $third] as $product) {
            SiteProduct::query()->create([
                'site_id' => $site->id,
                'central_product_id' => $product->id,
                'visibility' => 'visible',
            ]);
        }
        foreach ([$first, $third] as $product) {
            SiteSearchDocument::factory()->create([
                'site_id' => $site->id,
                'locale' => 'en',
                'document_id' => $product->id,
                'offers_count' => 1,
            ]);
        }

        $source = PriceSource::factory()->active()->create([
            'market_id' => $site->market_id,
            'name' => 'Coverage Feed',
        ]);
        $site->priceSources()->attach($source, ['enabled' => true]);
        $merchant = MarketMerchant::factory()->create(['market_id' => $site->market_id]);
        foreach ([$first, $third] as $product) {
            MarketOffer::factory()->create([
                'market_id' => $site->market_id,
                'market_merchant_id' => $merchant->id,
                'central_product_id' => $product->id,
                'price_source_id' => $source->id,
                'currency' => $site->market->currency_code,
            ]);
        }

        return [$site, $source];
    }
}

<?php

namespace Tests\Feature\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Enums\SiteStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\Site;
use App\Models\SiteProductProjection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnablesSiteLocales;
use Tests\TestCase;

class ProductOffersBlockTest extends TestCase
{
    use EnablesSiteLocales;
    use RefreshDatabase;

    public function test_product_page_renders_the_public_offers_block_with_valid_offers(): void
    {
        [$site, $product] = $this->productPageScenario();
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $merchant = MarketMerchant::factory()->create([
            'market_id' => $site->market_id,
            'name' => 'Example Merchant',
        ]);
        $offer = MarketOffer::factory()->create([
            'market_id' => $site->market_id,
            'market_merchant_id' => $merchant->id,
            'central_product_id' => $product->id,
            'price_source_id' => $source->id,
            'price' => '249.99',
            'currency' => $site->market->currency_code,
        ]);

        $this->get('http://offers.test/en/products/test-product')
            ->assertOk()
            ->assertSee('data-offers-block', false)
            ->assertSee('Where to buy')
            ->assertSee('Example Merchant')
            ->assertSee('249.99')
            ->assertSee('/offers/'.$offer->id.'/go', false)
            ->assertDontSee((string) $offer->url, false)
            ->assertSee('data-price-freshness="fresh"', false);
    }

    public function test_product_page_renders_a_safe_no_offers_state(): void
    {
        $this->productPageScenario();

        $this->get('http://offers.test/en/products/test-product')
            ->assertOk()
            ->assertSee('data-offers-block', false)
            ->assertSee('No current offers');
    }

    /** @return array{Site, CentralProduct} */
    private function productPageScenario(): array
    {
        $site = Site::factory()->create([
            'domain' => 'offers.test',
            'default_locale' => 'en',
            'status' => SiteStatus::Active,
        ]);
        $this->enableLocale($site, 'en');
        $product = CentralProduct::factory()->create(['name' => 'Test Product', 'slug' => 'test-product']);
        SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en',
            'central_product_id' => $product->id,
            'slug' => 'test-product',
            'title' => 'Test Product',
            'status' => ProjectionStatus::Active,
            'payload_json' => ['product' => ['title' => 'Test Product']],
            'media_json' => [],
        ]);

        return [$site, $product];
    }
}

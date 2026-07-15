<?php

namespace Tests\Feature\Pricing;

use App\Domains\Projections\SiteSyncService;
use App\Enums\MarketOfferStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSearchDocumentPriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_stores_the_lowest_valid_offer_price_in_the_site_search_document(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $product = CentralProduct::factory()->create();
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);

        foreach (['299.99', '249.99'] as $price) {
            MarketOffer::factory()->create([
                'market_id' => $site->market_id,
                'market_merchant_id' => MarketMerchant::factory()->create([
                    'market_id' => $site->market_id,
                ]),
                'central_product_id' => $product->id,
                'price_source_id' => $source->id,
                'price' => $price,
                'currency' => $site->market->currency_code,
                'status' => MarketOfferStatus::Active,
            ]);
        }

        $inactiveSource = PriceSource::factory()->create(['market_id' => $site->market_id]);
        MarketOffer::factory()->create([
            'market_id' => $site->market_id,
            'market_merchant_id' => MarketMerchant::factory()->create([
                'market_id' => $site->market_id,
            ]),
            'central_product_id' => $product->id,
            'price_source_id' => $inactiveSource->id,
            'price' => '199.99',
            'currency' => $site->market->currency_code,
            'status' => MarketOfferStatus::Active,
        ]);

        app(SiteSyncService::class)->syncProduct($site, $product, 'en');

        $this->assertDatabaseHas('site_search_documents', [
            'site_id' => $site->id,
            'locale' => 'en',
            'document_type' => 'product',
            'document_id' => $product->id,
            'min_price' => '249.99',
        ]);
    }

    public function test_product_without_valid_offers_has_no_minimum_price(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $product = CentralProduct::factory()->create();

        app(SiteSyncService::class)->syncProduct($site, $product, 'en');

        $this->assertDatabaseHas('site_search_documents', [
            'site_id' => $site->id,
            'document_type' => 'product',
            'document_id' => $product->id,
            'min_price' => null,
            'max_price' => null,
            'offers_count' => 0,
        ]);
    }

    public function test_it_stores_the_highest_valid_offer_price_in_the_site_search_document(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $product = CentralProduct::factory()->create();
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);

        foreach (['249.99', '329.99'] as $price) {
            MarketOffer::factory()->create([
                'market_id' => $site->market_id,
                'market_merchant_id' => MarketMerchant::factory()->create([
                    'market_id' => $site->market_id,
                ]),
                'central_product_id' => $product->id,
                'price_source_id' => $source->id,
                'price' => $price,
                'currency' => $site->market->currency_code,
                'status' => MarketOfferStatus::Active,
            ]);
        }

        app(SiteSyncService::class)->syncProduct($site, $product, 'en');

        $this->assertDatabaseHas('site_search_documents', [
            'site_id' => $site->id,
            'document_type' => 'product',
            'document_id' => $product->id,
            'min_price' => '249.99',
            'max_price' => '329.99',
        ]);
    }

    public function test_it_counts_only_valid_offers_in_the_site_search_document(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en']);
        $product = CentralProduct::factory()->create();
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);

        foreach (range(1, 3) as $offset) {
            MarketOffer::factory()->create([
                'market_id' => $site->market_id,
                'market_merchant_id' => MarketMerchant::factory()->create([
                    'market_id' => $site->market_id,
                ]),
                'central_product_id' => $product->id,
                'price_source_id' => $source->id,
                'price' => 200 + $offset,
                'currency' => $site->market->currency_code,
                'status' => MarketOfferStatus::Active,
            ]);
        }

        MarketOffer::factory()->create([
            'market_id' => $site->market_id,
            'market_merchant_id' => MarketMerchant::factory()->create([
                'market_id' => $site->market_id,
            ]),
            'central_product_id' => $product->id,
            'price_source_id' => $source->id,
            'currency' => $site->market->currency_code,
            'status' => MarketOfferStatus::Expired,
        ]);

        app(SiteSyncService::class)->syncProduct($site, $product, 'en');

        $this->assertDatabaseHas('site_search_documents', [
            'site_id' => $site->id,
            'document_type' => 'product',
            'document_id' => $product->id,
            'offers_count' => 3,
        ]);
    }
}

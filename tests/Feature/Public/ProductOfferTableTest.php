<?php

namespace Tests\Feature\Public;

use App\Enums\PriceFreshnessStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\Site;
use App\Services\Pricing\BestOfferResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ProductOfferTableTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_renders_offer_table_columns_actions_and_best_offer_first(): void
    {
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create();
        $source = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $other = $this->offer($site, $product, $source, 'Other Merchant', '300.00');
        $best = $this->offer($site, $product, $source, 'Best Merchant', '200.00');
        $offers = MarketOffer::query()->with('merchant')->whereIn('id', [$other->id, $best->id])->get();
        $bestOffer = app(BestOfferResolver::class)->resolve($site, $product);
        $freshness = $offers->mapWithKeys(fn (MarketOffer $offer): array => [
            (int) $offer->id => PriceFreshnessStatus::Fresh,
        ])->all();

        $html = Blade::render(
            '<x-public.offer-table :offers="$offers" :best-offer="$bestOffer" :freshness="$freshness" locale="en" />',
            compact('offers', 'bestOffer', 'freshness'),
        );

        $this->assertStringContainsString('data-offer-table', $html);
        foreach (['Merchant', 'Price', 'Availability', 'Delivery', 'Updated', 'Action'] as $heading) {
            $this->assertStringContainsString($heading, $html);
        }
        $this->assertMatchesRegularExpression('/Best Merchant.*Other Merchant/s', $html);
        $this->assertStringContainsString('Go to shop', $html);
        $this->assertStringNotContainsString((string) $best->url, $html);
    }

    private function offer(
        Site $site,
        CentralProduct $product,
        PriceSource $source,
        string $merchantName,
        string $price,
    ): MarketOffer {
        return MarketOffer::factory()->create([
            'market_id' => $site->market_id,
            'market_merchant_id' => MarketMerchant::factory()->create([
                'market_id' => $site->market_id,
                'name' => $merchantName,
            ]),
            'central_product_id' => $product->id,
            'price_source_id' => $source->id,
            'price' => $price,
            'currency' => $site->market->currency_code,
        ]);
    }
}

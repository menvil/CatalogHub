<?php

namespace Tests\Feature\Public;

use App\Data\Facets\FacetFilterSet;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use App\Services\Facets\FacetQueryBuilder;
use App\Services\Pricing\MerchantFilterOptionsBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ProductMerchantFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_products_by_one_or_multiple_merchants_with_valid_offers(): void
    {
        $scenario = $this->scenario();

        $cases = [
            [[$scenario['amazon']->id], [$scenario['amazonProduct']->id]],
            [
                [$scenario['amazon']->id, $scenario['mediaMarkt']->id],
                [$scenario['amazonProduct']->id, $scenario['mediaMarktProduct']->id],
            ],
        ];

        foreach ($cases as [$merchantIds, $expectedProductIds]) {
            $filters = FacetFilterSet::fromArray(['merchant_ids' => $merchantIds]);
            $results = app(FacetQueryBuilder::class)->apply(
                SiteSearchDocument::query(),
                $scenario['site'],
                $scenario['category'],
                $filters,
            )->get();

            $this->assertEqualsCanonicalizing($expectedProductIds, $results->pluck('document_id')->all());
            $this->assertArrayHasKey('merchant_ids', $filters->toQueryArray());
        }
    }

    public function test_disabled_source_offers_do_not_match_or_appear_as_filter_options(): void
    {
        $scenario = $this->scenario();
        $filters = FacetFilterSet::fromArray(['merchant_ids' => [$scenario['disabledMerchant']->id]]);

        $results = app(FacetQueryBuilder::class)->apply(
            SiteSearchDocument::query(),
            $scenario['site'],
            $scenario['category'],
            $filters,
        )->get();
        $options = app(MerchantFilterOptionsBuilder::class)->build(
            $scenario['site'],
            $scenario['category'],
        );

        $this->assertCount(3, $results);
        $this->assertArrayNotHasKey('merchant_ids', $filters->toQueryArray());
        $this->assertEqualsCanonicalizing(
            [$scenario['amazon']->id, $scenario['mediaMarkt']->id],
            $options->pluck('id')->all(),
        );
    }

    public function test_it_renders_only_current_merchant_options_in_public_facets(): void
    {
        $scenario = $this->scenario();
        $merchants = app(MerchantFilterOptionsBuilder::class)->build(
            $scenario['site'],
            $scenario['category'],
        );
        $filters = FacetFilterSet::fromArray(['merchant_ids' => [$scenario['amazon']->id]]);
        $html = Blade::render(
            '<x-public.facets.fields :facets="collect()" :filters="$filters" :merchants="$merchants" />',
            compact('filters', 'merchants'),
        );

        $this->assertStringContainsString('name="merchant_ids[]"', $html);
        $this->assertStringContainsString('Amazon', $html);
        $this->assertStringContainsString('MediaMarkt', $html);
        $this->assertStringNotContainsString('Disabled Shop', $html);
    }

    /**
     * @return array{
     *     site: Site,
     *     category: CentralCategory,
     *     amazon: MarketMerchant,
     *     mediaMarkt: MarketMerchant,
     *     disabledMerchant: MarketMerchant,
     *     amazonProduct: CentralProduct,
     *     mediaMarktProduct: CentralProduct
     * }
     */
    private function scenario(): array
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $activeSource = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $disabledSource = PriceSource::factory()->create(['market_id' => $site->market_id]);
        $amazon = MarketMerchant::factory()->create(['market_id' => $site->market_id, 'name' => 'Amazon']);
        $mediaMarkt = MarketMerchant::factory()->create(['market_id' => $site->market_id, 'name' => 'MediaMarkt']);
        $disabledMerchant = MarketMerchant::factory()->create([
            'market_id' => $site->market_id,
            'name' => 'Disabled Shop',
        ]);
        $products = collect(range(1, 3))->map(function () use ($site, $category): CentralProduct {
            $product = CentralProduct::factory()->create(['central_category_id' => $category->id]);
            SiteSearchDocument::factory()->create([
                'site_id' => $site->id,
                'document_id' => $product->id,
                'filter_values_json' => ['category_id' => $category->id],
            ]);

            return $product;
        });

        foreach ([
            [$amazon, $products[0], $activeSource],
            [$mediaMarkt, $products[1], $activeSource],
            [$disabledMerchant, $products[2], $disabledSource],
        ] as [$merchant, $product, $source]) {
            MarketOffer::factory()->create([
                'market_id' => $site->market_id,
                'market_merchant_id' => $merchant->id,
                'central_product_id' => $product->id,
                'price_source_id' => $source->id,
                'currency' => $site->market->currency_code,
            ]);
        }

        return [
            'site' => $site,
            'category' => $category,
            'amazon' => $amazon,
            'mediaMarkt' => $mediaMarkt,
            'disabledMerchant' => $disabledMerchant,
            'amazonProduct' => $products[0],
            'mediaMarktProduct' => $products[1],
        ];
    }
}

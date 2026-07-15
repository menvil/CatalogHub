<?php

namespace Tests\Feature\Public;

use App\Data\Facets\FacetFilterSet;
use App\Enums\PublicProductSort;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use App\Services\Facets\FacetQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductPriceSortTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sorts_products_by_minimum_price_ascending_with_null_prices_last(): void
    {
        [$site, $category] = $this->scenario();
        $filters = FacetFilterSet::fromArray(['sort' => 'price_asc']);

        $results = app(FacetQueryBuilder::class)->apply(
            SiteSearchDocument::query(),
            $site,
            $category,
            $filters,
        )->get();

        $this->assertSame(['100.00', '200.00', '300.00', null], $results->pluck('min_price')->all());
        $this->assertSame('price_asc', $filters->toQueryArray()['sort']);
        $this->assertSame('Price: low to high', PublicProductSort::options()['price_asc']);
    }

    /** @return array{Site, CentralCategory} */
    private function scenario(): array
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();

        foreach (['300.00', null, '100.00', '200.00'] as $price) {
            SiteSearchDocument::factory()->create([
                'site_id' => $site->id,
                'min_price' => $price,
                'filter_values_json' => ['category_id' => $category->id],
            ]);
        }

        return [$site, $category];
    }
}

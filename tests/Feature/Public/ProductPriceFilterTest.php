<?php

namespace Tests\Feature\Public;

use App\Data\Facets\FacetFilterSet;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use App\Services\Facets\FacetQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ProductPriceFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_products_by_price_from_price_to_and_combined_range(): void
    {
        [$site, $category] = $this->scenario();
        $cases = [
            [['price_from' => 150], ['200.00', '300.00']],
            [['price_to' => 250], ['100.00', '200.00']],
            [['price_from' => 150, 'price_to' => 250], ['200.00']],
        ];

        foreach ($cases as [$input, $expectedPrices]) {
            $filters = FacetFilterSet::fromArray($input);
            $results = app(FacetQueryBuilder::class)->apply(
                SiteSearchDocument::query(),
                $site,
                $category,
                $filters,
            )->get();

            $this->assertEqualsCanonicalizing($expectedPrices, $results->pluck('min_price')->all());
            $this->assertArrayHasKey(array_key_first($input), $filters->toQueryArray());
        }
    }

    public function test_products_without_a_minimum_price_are_excluded_from_price_results(): void
    {
        [$site, $category] = $this->scenario();

        $results = app(FacetQueryBuilder::class)->apply(
            SiteSearchDocument::query(),
            $site,
            $category,
            FacetFilterSet::fromArray(['price_from' => 0]),
        )->get();

        $this->assertCount(3, $results);
        $this->assertNotContains(null, $results->pluck('min_price')->all());
    }

    public function test_price_filter_fields_render_for_desktop_and_mobile_facets(): void
    {
        $filters = FacetFilterSet::fromArray(['price_from' => '100', 'price_to' => '250']);
        $template = <<<'BLADE'
            <x-public.facets.fields :facets="collect()" :filters="$filters" currency="EUR" :variant="$variant" />
            BLADE;

        foreach (['desktop', 'mobile'] as $variant) {
            $html = Blade::render($template, compact('filters', 'variant'));

            $this->assertStringContainsString('name="price_from"', $html);
            $this->assertStringContainsString('name="price_to"', $html);
            $this->assertStringContainsString('Price (EUR)', $html);
        }
    }

    /** @return array{Site, CentralCategory} */
    private function scenario(): array
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();

        foreach (['100.00', '200.00', '300.00', null] as $price) {
            SiteSearchDocument::factory()->create([
                'site_id' => $site->id,
                'min_price' => $price,
                'filter_values_json' => ['category_id' => $category->id],
            ]);
        }

        return [$site, $category];
    }
}

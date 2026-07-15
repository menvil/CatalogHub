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

class ProductInStockFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_filters_the_listing_to_in_stock_products_only(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $available = SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'in_stock' => true,
            'filter_values_json' => ['category_id' => $category->id],
        ]);
        SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'in_stock' => false,
            'filter_values_json' => ['category_id' => $category->id],
        ]);
        $filters = FacetFilterSet::fromArray(['in_stock' => '1']);

        $results = app(FacetQueryBuilder::class)->apply(
            SiteSearchDocument::query(),
            $site,
            $category,
            $filters,
        )->get();

        $this->assertSame([$available->id], $results->pluck('id')->all());
        $this->assertSame('1', $filters->toQueryArray()['in_stock']);
        $this->assertSame('In stock', $filters->appliedFilters()[0]->label);
    }

    public function test_false_or_invalid_in_stock_values_do_not_apply_the_filter(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();

        foreach ([true, false] as $inStock) {
            SiteSearchDocument::factory()->create([
                'site_id' => $site->id,
                'in_stock' => $inStock,
                'filter_values_json' => ['category_id' => $category->id],
            ]);
        }

        foreach (['0', 'invalid'] as $value) {
            $filters = FacetFilterSet::fromArray(['in_stock' => $value]);
            $results = app(FacetQueryBuilder::class)->apply(
                SiteSearchDocument::query(),
                $site,
                $category,
                $filters,
            )->get();

            $this->assertCount(2, $results);
            $this->assertArrayNotHasKey('in_stock', $filters->toQueryArray());
        }
    }

    public function test_it_renders_an_in_stock_checkbox_in_public_facets(): void
    {
        $filters = FacetFilterSet::fromArray(['in_stock' => '1']);
        $html = Blade::render(
            '<x-public.facets.fields :facets="collect()" :filters="$filters" />',
            compact('filters'),
        );

        $this->assertStringContainsString('name="in_stock"', $html);
        $this->assertStringContainsString('Only in-stock products', $html);
        $this->assertMatchesRegularExpression('/name="in_stock"[^>]*checked/s', $html);
    }
}

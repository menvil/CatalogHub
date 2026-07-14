<?php

namespace Tests\Feature\Facets;

use App\Data\Facets\FacetFilterSet;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use App\Services\Facets\FacetQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacetQueryBuilderBrandTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_search_documents_by_one_or_multiple_brand_slugs(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $lg = $this->document($site, $category, 'lg');
        $samsung = $this->document($site, $category, 'samsung');
        $this->document($site, $category, 'dell');
        $this->document(Site::factory()->create(), $category, 'lg');

        $filters = FacetFilterSet::fromArray(['brand' => ['samsung', 'lg']]);
        $results = app(FacetQueryBuilder::class)
            ->apply(SiteSearchDocument::query(), $site, $category, $filters)
            ->get();

        $this->assertCount(2, $results);
        $this->assertEqualsCanonicalizing([$lg->id, $samsung->id], $results->pluck('id')->all());
        $this->assertSame(['lg', 'samsung'], collect($filters->appliedFilters())->pluck('value')->all());
    }

    public function test_unknown_brand_returns_empty_result_without_central_catalog_lookup(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $this->document($site, $category, 'lg');

        $results = app(FacetQueryBuilder::class)->apply(
            SiteSearchDocument::query(),
            $site,
            $category,
            FacetFilterSet::fromArray(['brand' => ['unknown']]),
        )->get();

        $this->assertCount(0, $results);
    }

    private function document(Site $site, CentralCategory $category, string $brand): SiteSearchDocument
    {
        return SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'filter_values_json' => [
                'category_id' => $category->id,
                'brand_slug' => $brand,
            ],
        ]);
    }
}

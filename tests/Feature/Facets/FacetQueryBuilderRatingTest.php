<?php

namespace Tests\Feature\Facets;

use App\Data\Facets\FacetFilterSet;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use App\Services\Facets\FacetQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacetQueryBuilderRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_search_documents_by_minimum_rating(): void
    {
        [$site, $category] = $this->scenario();

        $filters = FacetFilterSet::fromArray(['rating_min' => 4]);
        $results = app(FacetQueryBuilder::class)
            ->apply(SiteSearchDocument::query(), $site, $category, $filters)
            ->get();

        $this->assertCount(1, $results);
        $this->assertSame(4.7, $results->first()->sort_values_json['rating']);
        $this->assertSame('4', $filters->appliedFilters()[0]->value);
    }

    public function test_invalid_rating_is_ignored_and_out_of_range_rating_is_clamped(): void
    {
        [$site, $category] = $this->scenario();

        $invalid = app(FacetQueryBuilder::class)->apply(
            SiteSearchDocument::query(),
            $site,
            $category,
            FacetFilterSet::fromArray(['rating_min' => 'excellent']),
        )->get();
        $aboveMaximum = app(FacetQueryBuilder::class)->apply(
            SiteSearchDocument::query(),
            $site,
            $category,
            FacetFilterSet::fromArray(['rating_min' => 6]),
        )->get();
        $belowMinimum = app(FacetQueryBuilder::class)->apply(
            SiteSearchDocument::query(),
            $site,
            $category,
            FacetFilterSet::fromArray(['rating_min' => -1]),
        )->get();

        $this->assertCount(2, $invalid);
        $this->assertCount(0, $aboveMaximum);
        $this->assertCount(2, $belowMinimum);
    }

    /** @return array{Site, CentralCategory} */
    private function scenario(): array
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();

        foreach ([4.7, 3.2] as $rating) {
            SiteSearchDocument::factory()->create([
                'site_id' => $site->id,
                'filter_values_json' => ['category_id' => $category->id],
                'sort_values_json' => ['rating' => $rating],
            ]);
        }

        return [$site, $category];
    }
}

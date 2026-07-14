<?php

namespace Tests\Feature\Facets;

use App\Data\Facets\FacetFilterSet;
use App\Enums\PublicProductSort;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use App\Services\Facets\FacetQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacetQueryBuilderSortingTest extends TestCase
{
    use RefreshDatabase;

    public function test_supports_default_rating_newest_and_name_sorts(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $older = $this->document($site, $category, 'Alpha', 3.0, '2026-01-01 00:00:00');
        $newer = $this->document($site, $category, 'Zulu', 4.8, '2026-02-01 00:00:00');

        $expectations = [
            PublicProductSort::Default->value => [$newer->id, $older->id],
            PublicProductSort::RatingDesc->value => [$newer->id, $older->id],
            PublicProductSort::Newest->value => [$newer->id, $older->id],
            PublicProductSort::NameAsc->value => [$older->id, $newer->id],
            PublicProductSort::NameDesc->value => [$newer->id, $older->id],
        ];

        foreach ($expectations as $sort => $expectedIds) {
            $results = app(FacetQueryBuilder::class)->apply(
                SiteSearchDocument::query(),
                $site,
                $category,
                FacetFilterSet::fromArray(['sort' => $sort]),
            )->get();

            $this->assertSame($expectedIds, $results->pluck('id')->all());
        }
    }

    public function test_unknown_sort_falls_back_to_default(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $older = $this->document($site, $category, 'Zulu', 5.0, '2026-01-01 00:00:00');
        $newer = $this->document($site, $category, 'Alpha', 1.0, '2026-02-01 00:00:00');

        $results = app(FacetQueryBuilder::class)->apply(
            SiteSearchDocument::query(),
            $site,
            $category,
            FacetFilterSet::fromArray(['sort' => 'price_asc']),
        )->get();

        $this->assertSame([$newer->id, $older->id], $results->pluck('id')->all());
    }

    private function document(
        Site $site,
        CentralCategory $category,
        string $title,
        float $rating,
        string $builtAt,
    ): SiteSearchDocument {
        return SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'title' => $title,
            'filter_values_json' => ['category_id' => $category->id],
            'sort_values_json' => ['rating' => $rating, 'title' => $title],
            'built_at' => $builtAt,
        ]);
    }
}

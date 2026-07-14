<?php

namespace Tests\Unit\Services;

use App\Data\Facets\AppliedFacetFilter;
use App\Data\Facets\FacetFilterSet;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use App\Services\Facets\FacetQueryBuilder;
use Tests\TestCase;

class FacetQueryBuilderTest extends TestCase
{
    public function test_no_op_apply_returns_same_query_builder(): void
    {
        $query = SiteSearchDocument::query();
        $filters = FacetFilterSet::fromArray([]);

        $result = app(FacetQueryBuilder::class)->apply(
            $query,
            Site::factory()->make(['market_id' => 1]),
            CentralCategory::factory()->make(),
            $filters,
        );

        $this->assertSame($query, $result);
    }

    public function test_filter_set_exposes_values_and_applied_filter_output(): void
    {
        $applied = new AppliedFacetFilter('brand', 'Brand', 'lg');
        $filters = FacetFilterSet::fromArray([
            'brand' => ['lg'],
            'empty' => '',
            '' => 'ignored',
        ])->withAppliedFilters([$applied]);

        $this->assertSame(['lg'], $filters->get('brand'));
        $this->assertFalse($filters->has('empty'));
        $this->assertSame([$applied], $filters->appliedFilters());
    }
}

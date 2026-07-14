<?php

namespace Tests\Unit\Support;

use App\Data\Facets\AppliedFacetFilter;
use App\Data\Facets\FacetFilterSet;
use App\Support\Facets\FacetUrlBuilder;
use Tests\TestCase;

class FacetUrlBuilderTest extends TestCase
{
    public function test_removes_one_filter_and_resets_page(): void
    {
        $url = app(FacetUrlBuilder::class)->removeFilter(
            currentUrl: '/monitors?brand=lg&panel_type=ips&page=2',
            filterKey: 'brand',
        );

        $this->assertSame('/monitors?panel_type=ips', $url);
    }

    public function test_removes_one_multi_select_value_and_preserves_the_rest(): void
    {
        $url = app(FacetUrlBuilder::class)->removeAppliedFilter(
            '/monitors',
            ['brand' => 'lg,samsung', 'panel_type' => 'ips', 'page' => 3],
            new AppliedFacetFilter('brand', 'LG', 'lg', ['brand']),
        );

        $this->assertSame('/monitors?brand=samsung&panel_type=ips', $url);
    }

    public function test_clear_all_returns_base_url_without_filters_page_or_sort(): void
    {
        $url = app(FacetUrlBuilder::class)->clearAll(
            '/monitors?brand=lg&panel_type=ips&page=2&sort=rating_desc',
        );

        $this->assertSame('/monitors', $url);
    }

    public function test_serializes_filter_set_into_deterministic_url(): void
    {
        $filters = FacetFilterSet::fromArray([
            'panel_type' => ['ips'],
            'brand' => ['samsung', 'lg'],
        ]);

        $url = app(FacetUrlBuilder::class)->toUrl('/monitors', $filters);

        $this->assertSame('/monitors?brand=lg,samsung&panel_type=ips', $url);
    }
}

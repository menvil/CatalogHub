<?php

namespace Tests\Unit\Data;

use App\Data\Facets\FacetFilterSet;
use Tests\TestCase;

class FacetFilterSetTest extends TestCase
{
    public function test_parses_filter_query_and_serializes_deterministically(): void
    {
        $filters = FacetFilterSet::fromQuery([
            'panel_type' => 'ips',
            'brand' => 'samsung,lg,lg',
            'refresh_rate_min' => ' 144 ',
            'page' => '2',
            'invalid key' => 'ignored',
            'empty' => '',
        ]);

        $this->assertSame(['lg', 'samsung'], $filters->get('brand'));
        $this->assertSame([
            'brand' => 'lg,samsung',
            'panel_type' => 'ips',
            'refresh_rate_min' => '144',
        ], $filters->toQueryArray());
        $this->assertTrue($filters->hasActiveFilters());
    }

    public function test_sort_alone_is_not_considered_an_active_facet_filter(): void
    {
        $filters = FacetFilterSet::fromQuery(['sort' => 'rating_desc']);

        $this->assertFalse($filters->hasActiveFilters());
    }

    public function test_normalizes_boolean_aliases_and_sort_values(): void
    {
        $filters = FacetFilterSet::fromQuery([
            'curved' => 'yes',
            'hdr' => 'false',
            'sort' => 'price_asc',
        ]);

        $this->assertSame([
            'curved' => '1',
            'hdr' => '0',
            'sort' => 'price_asc',
        ], $filters->toQueryArray());

        $unknownSort = FacetFilterSet::fromQuery(['sort' => 'unknown_sort']);

        $this->assertSame(['sort' => 'default'], $unknownSort->toQueryArray());
    }
}

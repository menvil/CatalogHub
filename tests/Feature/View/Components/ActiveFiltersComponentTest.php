<?php

namespace Tests\Feature\View\Components;

use App\Data\Facets\AppliedFacetFilter;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ActiveFiltersComponentTest extends TestCase
{
    public function test_renders_active_filter_chips_with_remove_and_clear_urls(): void
    {
        $filters = [
            new AppliedFacetFilter('brand', 'LG', 'lg', ['brand']),
            new AppliedFacetFilter('refresh_rate', 'Refresh rate', ['min' => '120'], [
                'refresh_rate_min',
                'refresh_rate_max',
            ]),
        ];

        $html = Blade::render(
            '<x-public.facets.active-filters :filters="$filters" base-url="/products" :query="$query" />',
            [
                'filters' => $filters,
                'query' => [
                    'brand' => 'lg,samsung',
                    'refresh_rate_min' => '120',
                    'page' => 2,
                ],
            ],
        );

        $this->assertStringContainsString('data-active-filters', $html);
        $this->assertStringContainsString('LG', $html);
        $this->assertStringContainsString('Refresh rate', $html);
        $this->assertStringContainsString('/products?brand=samsung&amp;refresh_rate_min=120', $html);
        $this->assertStringContainsString('/products?brand=lg,samsung', $html);
        $this->assertStringContainsString('Clear all', $html);
    }

    public function test_renders_nothing_when_there_are_no_active_filters(): void
    {
        $html = Blade::render(
            '<x-public.facets.active-filters :filters="[]" base-url="/products" :query="[]" />',
        );

        $this->assertSame('', trim($html));
    }
}

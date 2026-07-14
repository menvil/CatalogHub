<?php

namespace Tests\Feature\View\Components;

use App\Data\Facets\FacetDefinitionData;
use App\Data\Facets\FacetOptionData;
use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DesktopFilterSidebarTest extends TestCase
{
    public function test_renders_supported_facet_controls_and_hides_zero_count_options(): void
    {
        $facets = collect([
            $this->facet(FacetType::Checkbox, 'panel_type', options: [
                new FacetOptionData(1, 'ips', 'IPS', 10, count: 4),
                new FacetOptionData(2, 'va', 'Zero option', 20, count: 0),
            ]),
            $this->facet(FacetType::Range, 'refresh_rate'),
            $this->facet(FacetType::Boolean, 'curved'),
            $this->facet(FacetType::Select, 'resolution', options: [
                new FacetOptionData(3, '4k', '4K', 10, count: 2),
            ]),
        ]);

        $html = Blade::render(
            '<x-public.facets.desktop-sidebar :facets="$facets" :filters="$filters" action="/products" />',
            [
                'facets' => $facets,
                'filters' => ['panel_type' => ['ips'], 'refresh_rate_min' => '120'],
            ],
        );

        $this->assertStringContainsString('data-desktop-filter-sidebar', $html);
        $this->assertStringContainsString('name="panel_type[]"', $html);
        $this->assertStringContainsString('name="refresh_rate_min"', $html);
        $this->assertStringContainsString('name="refresh_rate_max"', $html);
        $this->assertStringContainsString('name="curved"', $html);
        $this->assertStringContainsString('name="resolution"', $html);
        $this->assertStringContainsString('IPS', $html);
        $this->assertStringNotContainsString('Zero option', $html);
    }

    /** @param list<FacetOptionData> $options */
    private function facet(FacetType $type, string $code, array $options = []): FacetDefinitionData
    {
        return new FacetDefinitionData(
            id: fake()->unique()->numberBetween(1, 10_000),
            code: $code,
            label: str($code)->headline()->toString(),
            type: $type,
            sourceType: FacetSourceType::Attribute,
            position: 10,
            isCollapsible: true,
            defaultCollapsed: false,
            options: $options,
            attributeCode: $code,
        );
    }
}

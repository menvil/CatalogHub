<?php

namespace Tests\Feature\View\Components;

use App\Data\Facets\FacetDefinitionData;
use App\Data\Facets\FacetOptionData;
use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class MobileFilterDrawerTest extends TestCase
{
    public function test_renders_mobile_drawer_trigger_controls_and_actions(): void
    {
        $facets = collect([
            new FacetDefinitionData(
                id: 1,
                code: 'panel_type',
                label: 'Panel type',
                type: FacetType::Checkbox,
                sourceType: FacetSourceType::Attribute,
                position: 10,
                isCollapsible: true,
                defaultCollapsed: false,
                options: [new FacetOptionData(1, 'ips', 'IPS', 10, count: 3)],
            ),
        ]);

        $html = Blade::render(
            '<x-public.facets.mobile-drawer :facets="$facets" :filters="[]" action="/products" />',
            ['facets' => $facets],
        );

        $this->assertStringContainsString('data-mobile-filter-drawer', $html);
        $this->assertStringContainsString('data-filter-drawer-open', $html);
        $this->assertStringContainsString('name="panel_type[]"', $html);
        $this->assertStringContainsString('Close filters', $html);
        $this->assertStringContainsString('Apply filters', $html);
        $this->assertStringContainsString('Clear filters', $html);
    }
}

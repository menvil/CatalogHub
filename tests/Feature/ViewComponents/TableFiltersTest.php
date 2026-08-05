<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class TableFiltersTest extends TestCase
{
    public function test_filter_bar_and_active_filters_expose_apply_and_clear_urls(): void
    {
        $active = [
            ['key' => 'status', 'label' => 'Status: Active', 'removeUrl' => '/brands?q=acme'],
            ['key' => 'market', 'label' => 'Market: DE', 'removeUrl' => '/brands?q=acme&status=active'],
        ];
        $html = Blade::render(<<<'BLADE'
            <x-admin.filter-bar action="/brands"><select name="status"><option>Active</option></select></x-admin.filter-bar>
            <x-admin.active-filters :filters="$active" clear-all-url="/brands" />
        BLADE, compact('active'));

        $this->assertStringContainsString('data-admin-filter-bar', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('method="GET"', $html);
        $this->assertStringContainsString('Status: Active', $html);
        $this->assertStringContainsString('href="/brands?q=acme"', $html);
        $this->assertStringContainsString('data-admin-clear-all-filters', $html);
        $this->assertStringContainsString('aria-label="Remove filter:', $html);
    }

    public function test_filter_and_toolbar_fallback_ids_are_unique_per_render(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-admin.filter-bar action="/brands">One</x-admin.filter-bar>
            <x-admin.filter-bar action="/brands">Two</x-admin.filter-bar>
            <x-admin.table-toolbar action="/brands" />
            <x-admin.table-toolbar action="/brands" />
        BLADE);

        preg_match_all('/data-admin-filter-open="([^"]+)"/', $html, $drawerIds);
        preg_match_all('/data-ui-form-field="([^"]+)"/', $html, $searchIds);

        $this->assertCount(2, array_unique($drawerIds[1]));
        $this->assertCount(2, array_unique($searchIds[1]));
        $this->assertSame(2, substr_count($html, 'role="search"'));
    }
}

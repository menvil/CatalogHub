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
        $this->assertStringContainsString('method="GET"', $html);
        $this->assertStringContainsString('Status: Active', $html);
        $this->assertStringContainsString('href="/brands?q=acme"', $html);
        $this->assertStringContainsString('data-admin-clear-all-filters', $html);
    }

    public function test_filter_drawer_script_has_open_close_and_escape_contracts(): void
    {
        $script = file_get_contents(resource_path('js/admin/filter-drawer.js'));
        $this->assertIsString($script);
        foreach (['data-admin-filter-open', 'data-admin-filter-close', 'Escape'] as $contract) {
            $this->assertStringContainsString($contract, $script);
        }
    }
}

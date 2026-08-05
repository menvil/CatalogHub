<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class LayoutPrimitivesTest extends TestCase
{
    public function test_card_section_and_detail_layout_preserve_heading_and_action_hierarchy(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-admin.detail-layout>
                <x-slot:main>
                    <x-admin.card title="Brand details" description="Canonical identity">
                        <x-ui.section title="Names"><p>Fields</p></x-ui.section>
                    </x-admin.card>
                </x-slot:main>
                <x-slot:aside><p>References</p></x-slot:aside>
                <x-slot:actions><button>Save</button></x-slot:actions>
            </x-admin.detail-layout>
        BLADE);

        $this->assertStringContainsString('data-admin-detail-layout', $html);
        $this->assertStringContainsString('data-admin-detail-main', $html);
        $this->assertStringContainsString('data-admin-detail-aside', $html);
        $this->assertStringContainsString('data-admin-sticky-actions', $html);
        $this->assertStringContainsString('<h2', $html);
        $this->assertStringContainsString('<h3', $html);
    }

    public function test_tabs_render_accessible_active_state_and_keyboard_contract(): void
    {
        $html = Blade::render(
            '<x-admin.tabs :items="$items" active="details" />',
            ['items' => [
                ['key' => 'details', 'label' => 'Details', 'url' => '#details', 'panelId' => 'details-panel'],
                ['key' => 'locales', 'label' => 'Locales', 'url' => '#locales', 'panelId' => 'locales-panel'],
            ]],
        );

        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('aria-selected="true"', $html);
        $this->assertStringContainsString('aria-controls="details-panel"', $html);
        $this->assertStringContainsString('data-admin-tabs', $html);

        $script = file_get_contents(resource_path('js/admin/tabs.js'));
        $this->assertIsString($script);
        foreach (['ArrowLeft', 'ArrowRight', 'Home', 'End'] as $key) {
            $this->assertStringContainsString($key, $script);
        }
    }
}

<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AdminDrawerTest extends TestCase
{
    public function test_admin_drawer_renders_title_body_footer_and_close_button(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-admin.drawer title="Product preview" position="right" size="lg">
                <p>Drawer body</p>

                <x-slot:footer>
                    <button type="button">Footer action</button>
                </x-slot:footer>
            </x-admin.drawer>
        BLADE);

        $this->assertStringContainsString('data-admin-drawer', $html);
        $this->assertStringContainsString('data-admin-drawer-contained="false"', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('Product preview', $html);
        $this->assertStringContainsString('Drawer body', $html);
        $this->assertStringContainsString('Footer action', $html);
        $this->assertStringContainsString('data-admin-drawer-close', $html);
        $this->assertStringContainsString('max-w-lg', $html);
    }

    public function test_admin_drawer_supports_left_position_closed_state_and_escaped_title(): void
    {
        $html = Blade::render(
            '<x-admin.drawer title="<Media details>" position="left" size="sm" :open="false">Body</x-admin.drawer>'
        );

        $this->assertStringContainsString('left-0 border-r', $html);
        $this->assertStringContainsString('max-w-sm', $html);
        $this->assertStringContainsString('hidden', $html);
        $this->assertStringContainsString('data-admin-drawer-open="false"', $html);
        $this->assertStringContainsString('&lt;Media details&gt;', $html);
        $this->assertStringNotContainsString('<Media details>', $html);
    }

    public function test_admin_drawer_supports_contained_previews_and_actions_slot(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-admin.drawer title="Preview" :contained="true">
                Body

                <x-slot:actions>
                    <button type="button">Save</button>
                </x-slot:actions>
            </x-admin.drawer>
        BLADE);

        $this->assertStringContainsString('absolute', $html);
        $this->assertStringContainsString('Save', $html);
    }

    public function test_admin_drawer_generates_unique_title_ids_for_multiple_instances(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-admin.drawer title="First">One</x-admin.drawer>
            <x-admin.drawer title="Second">Two</x-admin.drawer>
        BLADE);

        preg_match_all('/aria-labelledby="([^"]+)"/', $html, $matches);

        $this->assertCount(2, array_unique($matches[1]));
        $this->assertStringNotContainsString('id="admin-drawer-title"', $html);
    }
}

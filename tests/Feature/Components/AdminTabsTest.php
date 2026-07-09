<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AdminTabsTest extends TestCase
{
    public function test_admin_tabs_render_labels_counts_and_active_state(): void
    {
        $items = [
            ['key' => 'overview', 'label' => 'Overview', 'count' => null, 'url' => '/overview'],
            ['key' => 'specs', 'label' => 'Specs', 'count' => 12, 'url' => '/specs'],
            ['key' => 'media', 'label' => 'Media', 'count' => 4],
        ];

        $html = Blade::render(
            '<x-admin.tabs :items="$items" active="specs" />',
            ['items' => $items]
        );

        $this->assertStringContainsString('role="tablist"', $html);
        $this->assertStringContainsString('Overview', $html);
        $this->assertStringContainsString('Specs', $html);
        $this->assertStringContainsString('Media', $html);
        $this->assertStringContainsString('12', $html);
        $this->assertStringContainsString('aria-selected="true"', $html);
        $this->assertStringContainsString('tabindex="0"', $html);
        $this->assertStringContainsString('tabindex="-1"', $html);
        $this->assertStringContainsString('border-admin-primary text-admin-primary', $html);
    }

    public function test_admin_tabs_escape_labels_and_default_to_placeholder_urls(): void
    {
        $items = [
            ['key' => 'unsafe', 'label' => '<Unsafe>', 'count' => '<3>'],
        ];

        $html = Blade::render(
            '<x-admin.tabs :items="$items" active="missing" />',
            ['items' => $items]
        );

        $this->assertStringContainsString('href="#"', $html);
        $this->assertStringContainsString('&lt;Unsafe&gt;', $html);
        $this->assertStringContainsString('&lt;3&gt;', $html);
        $this->assertStringNotContainsString('<Unsafe>', $html);
    }

    public function test_admin_tabs_reject_unsafe_urls(): void
    {
        $items = [
            ['key' => 'unsafe', 'label' => 'Unsafe', 'url' => 'javascript:alert(1)'],
            ['key' => 'root', 'label' => 'Root', 'url' => '/admin'],
            ['key' => 'external', 'label' => 'External', 'url' => 'https://example.com'],
        ];

        $html = Blade::render('<x-admin.tabs :items="$items" active="unsafe" />', ['items' => $items]);

        $this->assertStringContainsString('href="#"', $html);
        $this->assertStringContainsString('href="/admin"', $html);
        $this->assertStringContainsString('href="https://example.com"', $html);
        $this->assertStringNotContainsString('javascript:alert(1)', $html);
    }
}

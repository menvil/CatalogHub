<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class StatusBadgeTest extends TestCase
{
    public function test_status_badge_renders_supported_variants(): void
    {
        foreach ([
            'success' => 'bg-admin-success-soft',
            'warning' => 'bg-admin-warning-soft',
            'danger' => 'bg-admin-danger-soft',
            'info' => 'bg-admin-info-soft',
            'neutral' => 'bg-admin-surface-muted',
        ] as $variant => $expectedClass) {
            $html = Blade::render(
                '<x-admin.status-badge label="Completed" variant="'.$variant.'" />'
            );

            $this->assertStringContainsString('Completed', $html);
            $this->assertStringContainsString('data-admin-status-badge="'.$variant.'"', $html);
            $this->assertStringContainsString($expectedClass, $html);
        }
    }

    public function test_status_badge_supports_icon_size_and_escaped_label(): void
    {
        $html = Blade::render(
            '<x-admin.status-badge label="<script>alert(1)</script>" variant="warning" icon="!" size="sm" />'
        );

        $this->assertStringContainsString('px-2 py-0.5 text-xs', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
        $this->assertStringContainsString('!', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }
}

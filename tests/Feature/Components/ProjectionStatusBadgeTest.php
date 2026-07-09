<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ProjectionStatusBadgeTest extends TestCase
{
    public function test_projection_status_badge_renders_supported_statuses(): void
    {
        foreach ([
            'synced' => ['label' => 'Synced', 'class' => 'bg-admin-success-soft'],
            'stale' => ['label' => 'Stale', 'class' => 'bg-admin-warning-soft'],
            'syncing' => ['label' => 'Syncing', 'class' => 'bg-admin-info-soft'],
            'failed' => ['label' => 'Failed', 'class' => 'bg-admin-danger-soft'],
            'missing' => ['label' => 'Missing', 'class' => 'bg-admin-surface-muted'],
        ] as $status => $expected) {
            $html = Blade::render(
                '<x-admin.projection-status-badge status="'.$status.'" />'
            );

            $this->assertStringContainsString($expected['label'], $html);
            $this->assertStringContainsString($expected['class'], $html);
            $this->assertStringContainsString('data-admin-projection-status="'.$status.'"', $html);
        }
    }

    public function test_projection_status_badge_renders_last_updated_and_custom_label(): void
    {
        $html = Blade::render(
            '<x-admin.projection-status-badge status="failed" label="<Needs sync>" last-updated="2026-07-08 12:00" />'
        );

        $this->assertStringContainsString('bg-admin-danger-soft', $html);
        $this->assertStringContainsString('2026-07-08 12:00', $html);
        $this->assertStringContainsString('&lt;Needs sync&gt;', $html);
        $this->assertStringNotContainsString('<Needs sync>', $html);
    }

    public function test_syncing_status_has_distinct_visual_marker(): void
    {
        $html = Blade::render('<x-admin.projection-status-badge status="syncing" />');

        $this->assertStringContainsString('h-2 w-2 rounded-full bg-admin-info', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }
}

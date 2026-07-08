<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class QualityWarningBadgeTest extends TestCase
{
    public function test_quality_warning_badge_renders_supported_levels(): void
    {
        foreach ([
            'low' => 'bg-admin-info-soft',
            'medium' => 'bg-admin-warning-soft',
            'high' => 'bg-orange-100',
            'critical' => 'bg-admin-danger-soft',
        ] as $level => $expectedClass) {
            $html = Blade::render(
                '<x-admin.quality-warning-badge label="Missing attributes" level="'.$level.'" />'
            );

            $this->assertStringContainsString('Missing attributes', $html);
            $this->assertStringContainsString('data-admin-quality-warning="'.$level.'"', $html);
            $this->assertStringContainsString($expectedClass, $html);
        }
    }

    public function test_quality_warning_badge_renders_count_and_escapes_label(): void
    {
        $html = Blade::render(
            '<x-admin.quality-warning-badge label="<strong>Media errors</strong>" level="critical" count="7" />'
        );

        $this->assertStringContainsString('Critical', $html);
        $this->assertStringContainsString('7', $html);
        $this->assertStringContainsString('&lt;strong&gt;Media errors&lt;/strong&gt;', $html);
        $this->assertStringNotContainsString('<strong>Media errors</strong>', $html);
    }
}

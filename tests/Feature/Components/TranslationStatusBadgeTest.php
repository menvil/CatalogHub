<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class TranslationStatusBadgeTest extends TestCase
{
    public function test_translation_status_badge_renders_supported_statuses(): void
    {
        foreach ([
            'missing' => 'Missing',
            'machine' => 'Machine',
            'reviewed' => 'Reviewed',
            'approved' => 'Approved',
            'outdated' => 'Outdated',
        ] as $status => $label) {
            $html = Blade::render(
                '<x-admin.translation-status-badge status="'.$status.'" />'
            );

            $this->assertStringContainsString($label, $html);
            $this->assertStringContainsString('data-admin-translation-status="'.$status.'"', $html);
        }
    }

    public function test_translation_status_badge_renders_locale_and_custom_label(): void
    {
        $html = Blade::render(
            '<x-admin.translation-status-badge status="outdated" locale="bg" label="<Needs review>" />'
        );

        $this->assertStringContainsString('bg', $html);
        $this->assertStringContainsString('bg-purple-100', $html);
        $this->assertStringContainsString('&lt;Needs review&gt;', $html);
        $this->assertStringNotContainsString('<Needs review>', $html);
    }
}

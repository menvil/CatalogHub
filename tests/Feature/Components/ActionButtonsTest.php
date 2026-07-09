<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ActionButtonsTest extends TestCase
{
    public function test_action_buttons_render_placeholder_buttons_disabled_by_default(): void
    {
        $actions = [
            ['label' => 'Approve'],
            ['name' => 'Reject'],
            ['value' => 'Missing label'],
        ];

        $html = Blade::render('<x-admin.action-buttons :actions="$actions" />', compact('actions'));

        $this->assertStringContainsString('Approve', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*>\s*Approve\s*<\/button>/s', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*>\s*Reject\s*<\/button>/s', $html);
        $this->assertStringNotContainsString('Array', $html);
        $this->assertStringNotContainsString('Missing label', $html);
    }

    public function test_action_buttons_render_href_actions_as_links(): void
    {
        $actions = [
            ['label' => 'Open review', 'href' => '/dev/ui-kit'],
        ];

        $html = Blade::render('<x-admin.action-buttons :actions="$actions" />', compact('actions'));

        $this->assertStringContainsString('href="/dev/ui-kit"', $html);
        $this->assertStringContainsString('Open review', $html);
        $this->assertStringNotContainsString('disabled', $html);
    }
}

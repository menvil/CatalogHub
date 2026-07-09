<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ActionButtonsTest extends TestCase
{
    public function test_action_buttons_render_labels_and_only_disable_when_configured(): void
    {
        $actions = [
            ['label' => 'Approve'],
            ['name' => 'Reject', 'disabled' => true],
            ['value' => 'Missing label'],
        ];

        $html = Blade::render('<x-admin.action-buttons :actions="$actions" />', compact('actions'));

        $this->assertStringContainsString('Approve', $html);
        $this->assertMatchesRegularExpression('/<button(?![^>]*disabled)[^>]*>\s*Approve\s*<\/button>/s', $html);
        $this->assertMatchesRegularExpression('/<button[^>]*disabled[^>]*>\s*Reject\s*<\/button>/s', $html);
        $this->assertStringNotContainsString('Array', $html);
        $this->assertStringNotContainsString('Missing label', $html);
    }
}

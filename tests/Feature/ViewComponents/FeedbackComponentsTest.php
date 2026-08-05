<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class FeedbackComponentsTest extends TestCase
{
    public function test_alerts_and_toasts_render_all_tones_and_escape_messages(): void
    {
        $html = Blade::render(<<<'BLADE'
            @foreach (['success', 'warning', 'danger', 'info', 'neutral'] as $tone)
                <x-ui.alert :tone="$tone" message="Alert <unsafe>" />
            @endforeach
            <x-ui.toast tone="success" message="Saved <unsafe>" dismissible />
        BLADE);

        foreach (['success', 'warning', 'danger', 'info', 'neutral'] as $tone) {
            $this->assertStringContainsString('data-ui-alert="'.$tone.'"', $html);
        }

        $this->assertStringContainsString('&lt;unsafe&gt;', $html);
        $this->assertStringNotContainsString('<unsafe>', $html);
        $this->assertStringContainsString('data-ui-feedback-dismiss', $html);
    }

    public function test_retry_is_explicit_and_feedback_script_only_retries_on_action(): void
    {
        $html = Blade::render('<x-ui.retry-block message="Could not load brands." retry-label="Try again" />');
        $script = file_get_contents(resource_path('js/admin/feedback.js'));

        $this->assertStringContainsString('Could not load brands.', $html);
        $this->assertStringContainsString('data-ui-retry', $html);
        $this->assertStringContainsString('data-ui-feedback-dismiss', $script);
        $this->assertStringNotContainsString('setInterval', $script);
        $this->assertStringNotContainsString('setTimeout', $script);
    }
}

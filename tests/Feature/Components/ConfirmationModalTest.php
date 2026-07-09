<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ConfirmationModalTest extends TestCase
{
    public function test_confirmation_modal_renders_title_message_confirm_and_cancel_buttons(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-admin.confirmation-modal
                title="Delete media"
                message="This action cannot be undone."
                confirm-label="Delete"
                cancel-label="Keep media"
                variant="danger"
            >
                <p>Additional context</p>
            </x-admin.confirmation-modal>
        BLADE);

        $this->assertStringContainsString('data-admin-modal', $html);
        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('Delete media', $html);
        $this->assertStringContainsString('This action cannot be undone.', $html);
        $this->assertStringContainsString('Additional context', $html);
        $this->assertStringContainsString('data-admin-modal-confirm', $html);
        $this->assertMatchesRegularExpression('/data-admin-modal-confirm[^>]*>\s*Delete\s*<\/button>/s', $html);
        $this->assertStringContainsString('Keep media', $html);
        $this->assertStringContainsString('bg-admin-danger', $html);
    }

    public function test_confirmation_modal_supports_warning_default_closed_and_contained_states(): void
    {
        $html = Blade::render(
            '<x-admin.confirmation-modal title="Run sync" message="Queue a sync job." variant="warning" :open="false" :contained="true" />'
        );

        $this->assertMatchesRegularExpression('/<div\s+[^>]*class="[^"]*absolute[^"]*"[^>]*data-admin-modal/s', $html);
        $this->assertStringContainsString('hidden', $html);
        $this->assertStringContainsString('data-admin-modal-open="false"', $html);
        $this->assertStringContainsString('bg-admin-warning', $html);
    }

    public function test_confirmation_modal_escapes_text_props(): void
    {
        $html = Blade::render(
            '<x-admin.confirmation-modal title="<Danger>" message="<Message>" confirm-label="<Yes>" cancel-label="<No>" />'
        );

        $this->assertStringContainsString('&lt;Danger&gt;', $html);
        $this->assertStringContainsString('&lt;Message&gt;', $html);
        $this->assertStringContainsString('&lt;Yes&gt;', $html);
        $this->assertStringContainsString('&lt;No&gt;', $html);
        $this->assertStringNotContainsString('<Danger>', $html);
    }

    public function test_confirmation_modal_generates_unique_accessibility_ids(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-admin.confirmation-modal title="First" message="One" />
            <x-admin.confirmation-modal title="Second" message="Two" />
        BLADE);

        preg_match_all('/aria-labelledby="([^"]+)"/', $html, $labelMatches);
        preg_match_all('/aria-describedby="([^"]+)"/', $html, $descriptionMatches);

        $this->assertCount(2, array_unique($labelMatches[1]));
        $this->assertCount(2, array_unique($descriptionMatches[1]));
        $this->assertStringContainsString('data-admin-modal-confirm', $html);
    }
}

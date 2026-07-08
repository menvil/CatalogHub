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
        $this->assertStringContainsString('Delete', $html);
        $this->assertStringContainsString('Keep media', $html);
        $this->assertStringContainsString('bg-admin-danger', $html);
    }

    public function test_confirmation_modal_supports_warning_default_closed_and_contained_states(): void
    {
        $html = Blade::render(
            '<x-admin.confirmation-modal title="Run sync" message="Queue a sync job." variant="warning" :open="false" :contained="true" />'
        );

        $this->assertStringContainsString('absolute', $html);
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
}

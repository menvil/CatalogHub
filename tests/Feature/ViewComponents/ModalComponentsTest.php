<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class ModalComponentsTest extends TestCase
{
    public function test_modal_and_destructive_confirmation_render_accessible_contracts(): void
    {
        $html = Blade::render(<<<'BLADE'
            <button type="button" data-admin-modal-open-target="details-modal">Open</button>
            <x-ui.modal id="details-modal" title="Details">Safe body</x-ui.modal>
            <x-ui.confirmation-dialog id="delete-modal" title="Delete brand" message="This cannot be undone." destructive confirm-label="Delete" />
        BLADE);

        $this->assertStringContainsString('role="dialog"', $html);
        $this->assertStringContainsString('aria-modal="true"', $html);
        $this->assertStringContainsString('data-admin-modal-open-target="details-modal"', $html);
        $this->assertStringContainsString('data-admin-modal="details-modal"', $html);
        $this->assertStringContainsString('data-destructive-confirmation', $html);
        $this->assertStringContainsString('Delete', $html);
    }

    public function test_confirmation_dialog_forwards_wrapper_attributes(): void
    {
        $html = Blade::render('<x-ui.confirmation-dialog id="delete-modal" title="Delete" message="Confirm" class="custom-dialog" data-owner="brands" />');

        $this->assertStringContainsString('custom-dialog', $html);
        $this->assertStringContainsString('data-owner="brands"', $html);
    }
}

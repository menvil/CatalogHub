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

    public function test_modal_height_tracks_its_fixed_or_contained_boundary(): void
    {
        $fixed = Blade::render('<x-ui.modal id="fixed-modal" title="Fixed">Body</x-ui.modal>');
        $contained = Blade::render('<x-ui.modal id="contained-modal" title="Contained" contained>Body</x-ui.modal>');

        $this->assertStringContainsString('max-h-[calc(100dvh-(var(--spacing-admin-page)*2))]', $fixed);
        $this->assertStringContainsString('max-h-full', $contained);
        $this->assertStringNotContainsString('100dvh', $contained);
    }
}

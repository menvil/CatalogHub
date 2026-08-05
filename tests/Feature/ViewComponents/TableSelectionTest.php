<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class TableSelectionTest extends TestCase
{
    public function test_bulk_and_row_action_contracts_use_stable_ids_and_safe_actions(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-admin.bulk-actions table-id="brands-table" :actions="[['id' => 'archive', 'label' => 'Archive']]" />
            <x-admin.row-actions row-id="brand-42" :actions="[['label' => 'Edit', 'url' => '/brands/42/edit'], ['label' => 'Delete', 'url' => '/brands/42', 'destructive' => true]]" />
        BLADE);

        $this->assertStringContainsString('data-admin-bulk-actions="brands-table"', $html);
        $this->assertStringContainsString('data-selected-count>0</span>', $html);
        $this->assertStringContainsString('data-admin-row-actions="brand-42"', $html);
        $this->assertStringContainsString('href="/brands/42/edit"', $html);
        $this->assertStringContainsString('data-destructive-action', $html);
    }

    public function test_selection_script_is_visible_page_scoped_and_clears_on_state_change(): void
    {
        $script = file_get_contents(resource_path('js/admin/table-selection.js'));
        $this->assertIsString($script);
        foreach (['data-admin-select-visible', 'data-admin-row-select', 'admin:table-state-changed'] as $contract) {
            $this->assertStringContainsString($contract, $script);
        }
        $this->assertStringNotContainsString('selectAcrossPages', $script);
    }
}

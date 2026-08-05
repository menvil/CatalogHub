<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;
use Tests\TestCase;

final class TableSelectionTest extends TestCase
{
    public function test_bulk_and_row_action_contracts_use_stable_ids_and_safe_actions(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-admin.bulk-actions table-id="brands-table" :actions="[['id' => 'archive', 'label' => 'Archive']]" />
            <x-admin.row-actions row-id="brand-42" :actions="[['label' => 'Edit', 'url' => '/brands/42/edit'], ['label' => 'Delete', 'url' => '/brands/42', 'destructive' => true, 'confirmationId' => 'delete-brand-42']]" />
        BLADE);

        $this->assertStringContainsString('data-admin-bulk-actions="brands-table"', $html);
        $this->assertStringContainsString('data-selected-count>0</span>', $html);
        $this->assertStringContainsString('data-admin-row-actions="brand-42"', $html);
        $this->assertStringContainsString('href="/brands/42/edit"', $html);
        $this->assertStringContainsString('data-destructive-action', $html);
        $this->assertStringContainsString('data-admin-modal-open-target="delete-brand-42"', $html);
        $this->assertStringContainsString('aria-controls="delete-brand-42"', $html);
        $this->assertStringNotContainsString('href="/brands/42"', $html);
    }

    public function test_selection_contract_is_limited_to_visible_rows(): void
    {
        $html = Blade::render('<x-admin.data-table table-id="brands" caption="Brands" :columns="$columns" :rows="$rows" selectable />', [
            'columns' => [['key' => 'name', 'label' => 'Name']],
            'rows' => [['id' => 'brand-1', 'name' => 'Acme'], ['id' => 'brand-2', 'name' => 'Northstar']],
        ]);

        $this->assertSame(1, substr_count($html, 'data-admin-select-visible'));
        $this->assertSame(2, substr_count($html, 'data-admin-row-select'));
        $this->assertStringNotContainsString('data-select-across-pages', $html);
    }

    public function test_destructive_row_actions_require_a_confirmation_target(): void
    {
        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('Destructive row actions require a confirmation ID.');

        Blade::render('<x-admin.row-actions row-id="brand-42" :actions="$actions" />', [
            'actions' => [['label' => 'Delete', 'url' => '/brands/42', 'destructive' => true]],
        ]);
    }
}

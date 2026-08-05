<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class FormStateTest extends TestCase
{
    public function test_form_state_exposes_dirty_submit_and_leave_warning_contract(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.form.form-state id="brand-form" action="/brands" method="post">
                <input name="name">
                <button type="submit">Save</button>
            </x-ui.form.form-state>
        BLADE);

        $this->assertStringContainsString('<form', $html);
        $this->assertStringContainsString('data-admin-form-state', $html);
        $this->assertStringContainsString('data-admin-form-dirty="false"', $html);
        $this->assertStringContainsString('data-admin-form-submitting="false"', $html);
        $this->assertStringContainsString('data-admin-form-leave-warning="true"', $html);
        $this->assertStringContainsString('method="POST"', $html);
    }

    public function test_form_state_can_disable_leave_warning_without_disabling_submit_guard(): void
    {
        $html = Blade::render('<x-ui.form.form-state id="filter-form" method="get" :leave-warning="false">Filters</x-ui.form.form-state>');

        $this->assertStringContainsString('data-admin-form-leave-warning="false"', $html);
        $this->assertStringContainsString('data-admin-form-state', $html);
    }

    public function test_form_state_script_defines_all_required_transitions(): void
    {
        $script = file_get_contents(resource_path('js/admin/form-state.js'));

        $this->assertIsString($script);
        foreach (['beforeunload', 'admin:form-saved', 'admin:form-invalid', 'data-admin-form-submitting'] as $contract) {
            $this->assertStringContainsString($contract, $script);
        }
    }
}

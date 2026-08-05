<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class ChoiceControlsTest extends TestCase
{
    public function test_checkbox_and_toggle_use_real_inputs_with_accessible_states(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.form.checkbox id="active" name="active" label="Active" checked error="Review" />
            <x-ui.form.toggle id="featured" name="featured" label="Featured" disabled />
        BLADE);

        $this->assertSame(2, substr_count($html, 'type="checkbox"'));
        $this->assertStringContainsString('role="switch"', $html);
        $this->assertMatchesRegularExpression('/<input[^>]*id="active"[^>]*\schecked(?:\s|>)/', $html);
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('data-ui-toggle-indicator', $html);
    }

    public function test_radio_group_exposes_group_label_and_one_selection(): void
    {
        $html = Blade::render(
            '<x-ui.form.radio-group id="status" name="status" label="Status" :options="$options" selected="active" />',
            ['options' => ['draft' => 'Draft', 'active' => 'Active']],
        );

        $this->assertStringContainsString('<fieldset', $html);
        $this->assertStringContainsString('<legend', $html);
        $this->assertSame(2, substr_count($html, 'type="radio"'));
        $this->assertSame(1, preg_match_all('/type="radio"[^>]*checked/', $html));
    }
}

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
        $this->assertStringContainsString('data-ui-toggle-hit-area', $html);
        $this->assertStringContainsString('cursor-pointer', $html);
    }

    public function test_checkbox_list_submits_multiple_checked_values_with_a_group_label(): void
    {
        $html = Blade::render(
            '<x-ui.form.checkbox-list id="markets" name="markets" label="Markets" :options="$options" :selected="$selected" />',
            ['options' => ['de' => 'Germany', 'at' => 'Austria', 'ch' => 'Switzerland'], 'selected' => ['de', 'at']],
        );

        $this->assertStringContainsString('data-ui-checkbox-list="markets"', $html);
        $this->assertStringContainsString('<legend', $html);
        $this->assertSame(3, substr_count($html, 'name="markets[]"'));
        $this->assertSame(2, preg_match_all('/type="checkbox"[^>]*checked/', $html));
        $this->assertStringContainsString('cursor-pointer', $html);
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

    public function test_radio_group_merges_caller_and_field_descriptions_without_duplicate_attributes(): void
    {
        $html = Blade::render(
            '<x-ui.form.radio-group id="status" name="status" label="Status" :options="$options" help="Choose status" error="Invalid" aria-describedby="status-guidance" />',
            ['options' => ['draft' => 'Draft']],
        );

        $this->assertSame(1, substr_count($html, 'aria-describedby='));
        $this->assertStringContainsString('aria-describedby="status-guidance status-help status-error"', $html);
    }
}

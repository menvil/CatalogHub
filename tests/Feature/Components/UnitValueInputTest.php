<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class UnitValueInputTest extends TestCase
{
    public function test_unit_value_input_renders_numeric_input_unit_selector_and_canonical_preview(): void
    {
        $availableUnits = [
            ['value' => 'w', 'label' => 'W'],
            ['value' => 'kw', 'label' => 'kW'],
        ];

        $html = Blade::render(
            '<x-admin.unit-value-input label="Power" value="100" unit="w" :available-units="$availableUnits" canonical-preview="100 W" />',
            compact('availableUnits')
        );

        $this->assertStringContainsString('data-admin-unit-value-input', $html);
        $this->assertStringContainsString('type="number"', $html);
        $this->assertStringContainsString('value="100"', $html);
        $this->assertStringContainsString('<select', $html);
        $this->assertMatchesRegularExpression('/<div\b[^>]*\sdata-ui-select(?:\s[^>]*)?>/', $html);
        $this->assertStringContainsString('data-ui-select-trigger', $html);
        $this->assertStringContainsString('W', $html);
        $this->assertStringContainsString('kW', $html);
        $this->assertStringContainsString('value="w" selected', $html);
        $this->assertStringContainsString('Canonical preview:', $html);
        $this->assertStringContainsString('100 W', $html);
    }

    public function test_unit_value_input_maps_scalar_options_and_falls_back_to_an_array_code(): void
    {
        $availableUnits = ['liter', 'gallon', ['code' => 'kg']];

        $html = Blade::render(
            '<x-admin.unit-value-input label="Volume" :available-units="$availableUnits" />',
            compact('availableUnits')
        );

        $this->assertMatchesRegularExpression('/<option value="liter"[^>]*>LITER<\/option>/', $html);
        $this->assertMatchesRegularExpression('/<option value="gallon"[^>]*>GALLON<\/option>/', $html);
        $this->assertMatchesRegularExpression('/<option value="kg"[^>]*>kg<\/option>/', $html);
    }

    public function test_unit_value_input_uses_a_synchronized_placeholder_when_no_unit_is_selected(): void
    {
        $availableUnits = [['value' => 'w', 'label' => 'W']];

        $html = Blade::render(
            '<x-admin.unit-value-input label="Power" :available-units="$availableUnits" />',
            compact('availableUnits')
        );

        $this->assertStringContainsString('<option value="">Select a unit</option>', $html);
        $this->assertMatchesRegularExpression('/data-ui-select-value>\s*Select a unit\s*<\/span>/', $html);
        $this->assertMatchesRegularExpression('/data-ui-select-option data-value=""[^>]*>Select a unit<\/button>/', $html);
    }

    public function test_unit_value_input_renders_error_state_and_default_preview(): void
    {
        $availableUnits = ['liter', 'gallon'];

        $html = Blade::render(
            '<x-admin.unit-value-input label="Volume" :available-units="$availableUnits" error="Value is required" />',
            compact('availableUnits')
        );

        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('border-admin-danger', $html);
        $this->assertStringContainsString('Value is required', $html);
        $this->assertStringContainsString('Not calculated in Phase 2', $html);
    }

    public function test_unit_value_input_preserves_zero_preview_and_generates_unique_ids(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-admin.unit-value-input canonical-preview="0" />
            <x-admin.unit-value-input canonical-preview="0.0" />
        BLADE);

        preg_match_all('/<input\s+id="(unit-value-value-[^"]+)"\s+type="number"/', $html, $matches);

        $this->assertCount(2, $matches[1]);
        $this->assertCount(count($matches[1]), array_unique($matches[1]));
        foreach ($matches[1] as $inputId) {
            $this->assertStringContainsString('id="'.$inputId.'"', $html);
            $this->assertStringContainsString('id="'.$inputId.'-unit"', $html);
            $this->assertStringContainsString('id="'.$inputId.'-unit-trigger"', $html);
            $this->assertStringContainsString('id="'.$inputId.'-unit-menu"', $html);
        }
        $this->assertStringContainsString('>0</span>', $html);
        $this->assertStringContainsString('>0.0</span>', $html);
    }

    public function test_unit_value_input_escapes_labels_and_errors(): void
    {
        $availableUnits = [['value' => '<w>', 'label' => '<Watt>']];

        $html = Blade::render(
            '<x-admin.unit-value-input label="<Power>" :available-units="$availableUnits" error="<Invalid>" />',
            compact('availableUnits')
        );

        $this->assertStringContainsString('&lt;Power&gt;', $html);
        $this->assertStringContainsString('&lt;w&gt;', $html);
        $this->assertStringContainsString('&lt;Watt&gt;', $html);
        $this->assertStringContainsString('&lt;Invalid&gt;', $html);
        $this->assertStringNotContainsString('<Power>', $html);
    }
}

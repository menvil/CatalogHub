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
        $this->assertStringContainsString('W', $html);
        $this->assertStringContainsString('kW', $html);
        $this->assertStringContainsString('selected', $html);
        $this->assertStringContainsString('Canonical preview:', $html);
        $this->assertStringContainsString('100 W', $html);
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

    public function test_unit_value_input_escapes_labels_and_errors(): void
    {
        $availableUnits = [['value' => '<w>', 'label' => '<Watt>']];

        $html = Blade::render(
            '<x-admin.unit-value-input label="<Power>" :available-units="$availableUnits" error="<Invalid>" />',
            compact('availableUnits')
        );

        $this->assertStringContainsString('&lt;Power&gt;', $html);
        $this->assertStringContainsString('&lt;Watt&gt;', $html);
        $this->assertStringContainsString('&lt;Invalid&gt;', $html);
        $this->assertStringNotContainsString('<Power>', $html);
    }
}

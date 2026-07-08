<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AttributeValueEditorTest extends TestCase
{
    public function test_attribute_value_editor_renders_label_raw_value_and_normalized_area(): void
    {
        $html = Blade::render(
            '<x-admin.attribute-value-editor attribute-label="Refresh rate" attribute-code="refresh_rate" data-type="number" raw-value="120hz" normalized-value="120" confidence="86" source-label="Importer" />'
        );

        $this->assertStringContainsString('data-admin-attribute-value-editor="number"', $html);
        $this->assertStringContainsString('Refresh rate', $html);
        $this->assertStringContainsString('refresh_rate', $html);
        $this->assertStringContainsString('Raw value', $html);
        $this->assertStringContainsString('120hz', $html);
        $this->assertStringContainsString('Normalized preview', $html);
        $this->assertStringContainsString('120', $html);
        $this->assertStringContainsString('86% confidence', $html);
        $this->assertStringContainsString('Source: Importer', $html);
    }

    public function test_attribute_value_editor_renders_type_specific_unit_enum_boolean_and_text_shells(): void
    {
        $unitOptions = [['value' => 'w', 'label' => 'W']];
        $options = ['A', 'B'];

        $unit = Blade::render(
            '<x-admin.attribute-value-editor attribute-label="Power" data-type="unit" normalized-value="100" :unit-options="$unitOptions" />',
            compact('unitOptions')
        );
        $enum = Blade::render(
            '<x-admin.attribute-value-editor attribute-label="Energy class" data-type="enum" :options="$options" />',
            compact('options')
        );
        $boolean = Blade::render('<x-admin.attribute-value-editor attribute-label="Portable" data-type="boolean" normalized-value="1" />');
        $text = Blade::render('<x-admin.attribute-value-editor attribute-label="Color" data-type="text" normalized-value="Black" />');

        $this->assertStringContainsString('data-admin-unit-value-input', $unit);
        $this->assertStringContainsString('Options placeholder', $enum);
        $this->assertStringContainsString('Boolean value placeholder', $boolean);
        $this->assertStringContainsString('Text value placeholder', $text);
    }

    public function test_attribute_value_editor_escapes_values(): void
    {
        $html = Blade::render(
            '<x-admin.attribute-value-editor attribute-label="<Label>" raw-value="<Raw>" normalized-value="<Normalized>" />'
        );

        $this->assertStringContainsString('&lt;Label&gt;', $html);
        $this->assertStringContainsString('&lt;Raw&gt;', $html);
        $this->assertStringContainsString('&lt;Normalized&gt;', $html);
        $this->assertStringNotContainsString('<Label>', $html);
    }
}

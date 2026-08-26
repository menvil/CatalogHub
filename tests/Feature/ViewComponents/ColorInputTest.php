<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class ColorInputTest extends TestCase
{
    public function test_renders_text_picker_value_and_accessible_error(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.form.color-input
                id="brand-color"
                name="primary_color"
                label="Primary color"
                value="#1428a0"
                help="Canonical hex"
                error="Invalid color"
            />
        BLADE);

        $this->assertStringContainsString('name="primary_color"', $html);
        $this->assertStringContainsString('value="#1428a0"', $html);
        $this->assertStringContainsString('type="color"', $html);
        $this->assertStringContainsString('value="#1428A0"', $html);
        $this->assertStringContainsString('aria-label="Choose Primary color"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('aria-describedby="brand-color-help brand-color-error"', $html);
        $this->assertStringContainsString('Invalid color', $html);
    }

    public function test_invalid_old_input_remains_visible_and_disabled_state_disables_both_controls(): void
    {
        $invalid = Blade::render('<x-ui.form.color-input id="invalid-color" name="color" label="Color" value="#123" />');
        $disabled = Blade::render('<x-ui.form.color-input id="disabled-color" name="color" label="Color" value="#64748B" disabled />');

        $this->assertStringContainsString('value="#123"', $invalid);
        $this->assertStringContainsString('value="#000000"', $invalid);
        $this->assertSame(2, preg_match_all('/\sdisabled(?:\s|>)/', $disabled));
    }
}

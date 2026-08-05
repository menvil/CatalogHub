<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class FormFieldTest extends TestCase
{
    public function test_field_associates_required_label_help_and_escaped_error(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.form.field id="brand-name" label="Brand name" required help="Public identity" error="<unsafe>">
                <input id="brand-name" aria-describedby="brand-name-help brand-name-error">
            </x-ui.form.field>
        BLADE);

        $this->assertStringContainsString('for="brand-name"', $html);
        $this->assertStringContainsString('aria-hidden="true">*</span>', $html);
        $this->assertStringContainsString('id="brand-name-help"', $html);
        $this->assertStringContainsString('id="brand-name-error"', $html);
        $this->assertStringContainsString('role="alert"', $html);
        $this->assertStringContainsString('&lt;unsafe&gt;', $html);
    }
}

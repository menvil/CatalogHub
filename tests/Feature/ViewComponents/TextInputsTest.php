<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class TextInputsTest extends TestCase
{
    public function test_text_and_textarea_render_value_constraints_and_accessible_errors(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.form.input id="brand-name" name="name" label="Name" value="Acme" maxlength="80" required error="Required" />
            <x-ui.form.textarea id="brand-summary" name="summary" label="Summary" :value="$value" maxlength="240" readonly help="Plain text" />
        BLADE, ['value' => '<unsafe>']);

        $this->assertStringContainsString('value="Acme"', $html);
        $this->assertStringContainsString('maxlength="80"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('readonly', $html);
        $this->assertStringContainsString('&lt;unsafe&gt;', $html);
        $this->assertStringNotContainsString('<unsafe>', $html);
    }

    public function test_slug_input_has_visible_prefix_without_mutating_value(): void
    {
        $html = Blade::render('<x-ui.form.slug-input id="brand-slug" name="slug" label="Slug" prefix="/brands/" value="existing-slug" disabled />');

        $this->assertStringContainsString('/brands/', $html);
        $this->assertStringContainsString('value="existing-slug"', $html);
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringNotContainsString('data-auto-slug', $html);
    }
}

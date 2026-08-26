<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class TagInputTest extends TestCase
{
    public function test_it_renders_accessible_chips_hidden_inputs_and_form_association(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.form.tag-input
                id="brand-tags"
                name="tags"
                label="Brand tags"
                :values="['Premium', 'C++']"
                help="Maximum 20 tags."
                error="Invalid tag."
                form="tag-form"
            />
        BLADE);

        self::assertStringContainsString('for="brand-tags-input"', $html);
        self::assertStringContainsString('name="tags[]"', $html);
        self::assertStringContainsString('value="Premium"', $html);
        self::assertStringContainsString('form="tag-form"', $html);
        self::assertStringContainsString('aria-label="Remove Premium"', $html);
        self::assertStringContainsString('aria-label="Remove C++"', $html);
        self::assertStringContainsString('aria-describedby="brand-tags-help brand-tags-error brand-tags-client-error"', $html);
        self::assertStringContainsString('aria-invalid="true"', $html);
    }

    public function test_empty_and_disabled_states_render_without_fake_values_and_disable_controls(): void
    {
        $empty = Blade::render('<x-ui.form.tag-input id="empty-tags" name="tags" label="Tags" :values="[]" />');
        $disabled = Blade::render('<x-ui.form.tag-input id="disabled-tags" name="tags" label="Tags" :values="[\'Enterprise\']" disabled />');

        self::assertStringNotContainsString('<input type="hidden" name="tags[]"', $empty);
        self::assertStringContainsString('data-ui-tag-input-disabled="true"', $disabled);
        self::assertGreaterThanOrEqual(3, preg_match_all('/\sdisabled(?:\s|>)/', $disabled));
    }
}

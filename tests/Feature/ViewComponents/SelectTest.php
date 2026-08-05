<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class SelectTest extends TestCase
{
    public function test_native_select_supports_groups_selection_empty_and_invalid_state(): void
    {
        $options = ['Europe' => ['de' => 'Germany', 'fr' => 'France'], 'us' => 'United States'];
        $html = Blade::render(
            '<x-ui.form.select id="market" name="market" label="Market" :options="$options" selected="fr" placeholder="Choose" error="Invalid" />',
            compact('options'),
        );

        $this->assertStringContainsString('<optgroup label="Europe">', $html);
        $this->assertMatchesRegularExpression('/value="fr"[^>]*selected/', $html);
        $this->assertStringContainsString('Choose', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
    }

    public function test_multi_select_uses_a_real_multiple_select(): void
    {
        $html = Blade::render(
            '<x-ui.form.multi-select id="locales" name="locales" label="Locales" :options="$options" :selected="$selected" disabled />',
            ['options' => ['de-DE' => 'German', 'en-DE' => 'English'], 'selected' => ['de-DE']],
        );

        $this->assertStringContainsString('name="locales[]"', $html);
        $this->assertStringContainsString('multiple', $html);
        $this->assertMatchesRegularExpression('/value="de-DE"[^>]*selected/', $html);
        $this->assertStringContainsString('disabled', $html);
    }

    public function test_select_merges_caller_and_field_descriptions_without_duplicate_attributes(): void
    {
        $html = Blade::render(
            '<x-ui.form.select id="market" name="market" label="Market" :options="[]" help="Choose one" error="Invalid" aria-describedby="market-guidance" />'
        );

        $this->assertSame(1, substr_count($html, 'aria-describedby='));
        $this->assertStringContainsString('aria-describedby="market-guidance market-help market-error"', $html);
    }
}

<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class LocalizedFieldEditorTest extends TestCase
{
    public function test_localized_field_editor_renders_locale_tabs_and_field_values(): void
    {
        $locales = [
            ['code' => 'en', 'label' => 'English'],
            ['code' => 'bg', 'label' => 'Bulgarian'],
        ];
        $values = ['en' => 'Coffee machine', 'bg' => ''];
        $statuses = ['en' => 'approved', 'bg' => 'missing'];

        $html = Blade::render(
            '<x-admin.localized-field-editor field-name="Product title" :locales="$locales" :values="$values" :statuses="$statuses" />',
            compact('locales', 'values', 'statuses')
        );

        $this->assertStringContainsString('data-admin-localized-field-editor="tabs"', $html);
        $this->assertStringNotContainsString('role="tablist"', $html);
        $this->assertStringNotContainsString('role="tab"', $html);
        $this->assertStringNotContainsString('aria-selected=', $html);
        $this->assertStringContainsString('data-admin-locale-summary="en"', $html);
        $this->assertStringContainsString('Product title', $html);
        $this->assertStringContainsString('English', $html);
        $this->assertStringContainsString('Bulgarian', $html);
        $this->assertStringContainsString('Coffee machine', $html);
        $this->assertStringContainsString('Missing localized value', $html);
        $this->assertStringContainsString('data-admin-translation-status="approved"', $html);
        $this->assertStringContainsString('data-admin-translation-status="missing"', $html);
    }

    public function test_localized_field_editor_supports_stacked_mode_and_string_locales(): void
    {
        $locales = ['en', 'de'];
        $values = ['de' => 'Kaffeemaschine'];

        $html = Blade::render(
            '<x-admin.localized-field-editor field-name="Category name" :locales="$locales" :values="$values" mode="stacked" />',
            compact('locales', 'values')
        );

        $this->assertStringContainsString('data-admin-localized-field-editor="stacked"', $html);
        $this->assertStringContainsString('EN', $html);
        $this->assertStringContainsString('DE', $html);
        $this->assertStringContainsString('Kaffeemaschine', $html);
        $this->assertStringContainsString('md:grid-cols-2', $html);
    }

    public function test_localized_field_editor_escapes_field_values(): void
    {
        $locales = ['en'];
        $values = ['en' => '<Unsafe title>'];

        $html = Blade::render(
            '<x-admin.localized-field-editor field-name="<Title>" :locales="$locales" :values="$values" />',
            compact('locales', 'values')
        );

        $this->assertStringContainsString('&lt;Title&gt;', $html);
        $this->assertStringContainsString('&lt;Unsafe title&gt;', $html);
        $this->assertStringNotContainsString('<Unsafe title>', $html);
    }
}

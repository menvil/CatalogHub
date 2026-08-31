<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class SearchableSelectTest extends TestCase
{
    public function test_component_renders_accessible_search_selection_clear_and_error_contracts(): void
    {
        $options = [
            ['value' => '1', 'label' => 'Germany (DE)', 'search' => 'Germany Deutschland DE DEU'],
            ['value' => '2', 'label' => 'South Korea (KR)', 'search' => 'South Korea KR KOR'],
        ];
        $html = Blade::render(
            '<x-ui.form.searchable-select id="country" name="country_id" label="Country" :options="$options" selected="2" error="Invalid Country" clearable />',
            compact('options'),
        );

        $this->assertStringContainsString('name="country_id"', $html);
        $this->assertStringContainsString('role="combobox"', $html);
        $this->assertStringContainsString('aria-expanded="false"', $html);
        $this->assertStringContainsString('aria-controls="country-listbox"', $html);
        $this->assertStringContainsString('role="listbox"', $html);
        $this->assertStringContainsString('role="option"', $html);
        $this->assertStringContainsString('aria-invalid="true"', $html);
        $this->assertStringContainsString('value="South Korea (KR)"', $html);
        $this->assertStringContainsString('data-search="Germany Deutschland DE DEU"', $html);
        $this->assertStringContainsString('aria-label="Clear Country"', $html);
        $this->assertStringContainsString('for="country-combobox"', $html);
    }

    public function test_component_supports_disabled_and_empty_initial_selection(): void
    {
        $html = Blade::render(
            '<x-ui.form.searchable-select id="country" name="country_id" label="Country" :options="[]" disabled />',
        );

        $this->assertGreaterThanOrEqual(2, substr_count($html, 'disabled'));
        $this->assertStringContainsString('value=""', $html);
        $this->assertStringContainsString('No matching options.', $html);
    }

    public function test_component_supports_bounded_server_side_search_without_embedding_the_directory(): void
    {
        $html = Blade::render(
            '<x-ui.form.searchable-select id="organization" name="organization_id" label="Organization" :options="[]" :remote="\'/organizations/search\'" />',
        );

        $this->assertStringContainsString('data-search-url="/organizations/search"', $html);
        $this->assertStringContainsString('data-ui-searchable-select-loading', $html);
        $this->assertSame(1, substr_count($html, '<option'));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class DateTimeFileInputsTest extends TestCase
{
    public function test_date_picker_has_an_explicit_modern_calendar_popup_and_form_value(): void
    {
        $html = Blade::render('<x-ui.form.date-picker id="publish-date" name="publish_date" label="Publish date" value="2026-08-12" min="2026-08-01" max="2026-09-01" />');

        $this->assertStringContainsString('type="hidden"', $html);
        $this->assertStringContainsString('value="2026-08-12"', $html);
        $this->assertStringContainsString('data-ui-date-picker="publish-date"', $html);
        $this->assertStringContainsString('data-ui-date-picker-trigger', $html);
        $this->assertStringContainsString('data-ui-date-picker-panel', $html);
        $this->assertStringContainsString('data-ui-date-picker-grid', $html);
        $this->assertStringContainsString('cursor-pointer', $html);
        $this->assertStringContainsString('data-foundation-icon="calendar-days"', $html);
    }

    public function test_date_picker_does_not_render_an_overflowed_calendar_date(): void
    {
        $html = Blade::render('<x-ui.form.date-picker id="publish-date" name="publish_date" label="Publish date" value="2026-02-31" />');

        $this->assertStringContainsString('value="2026-02-31"', $html);
        $this->assertStringContainsString('Choose publish date', $html);
        $this->assertStringNotContainsString('03 Mar 2026', $html);
    }

    public function test_date_time_shell_renders_constraints_and_explicit_timezone(): void
    {
        $html = Blade::render('<x-ui.form.date-time id="publish-at" name="publish_at" label="Publish at" value="2026-08-05T12:00" min="2026-08-01T00:00" max="2026-09-01T00:00" timezone="Europe/Berlin" />');

        $this->assertStringContainsString('data-ui-date-picker-mode="datetime"', $html);
        $this->assertStringContainsString('data-ui-date-picker-time', $html);
        $this->assertStringContainsString('data-min="2026-08-01T00:00"', $html);
        $this->assertStringContainsString('data-max="2026-09-01T00:00"', $html);
        $this->assertStringContainsString('value="2026-08-05T12:00"', $html);
        $this->assertStringContainsString('Timezone: Europe/Berlin', $html);
    }

    public function test_file_shell_only_renders_selection_contract_without_upload_logic(): void
    {
        $html = Blade::render('<x-ui.form.file-input id="asset" name="asset" label="Asset" accept="image/png,image/jpeg" hint="PNG or JPEG, up to 5 MB" multiple disabled />');

        $this->assertStringContainsString('type="file"', $html);
        $this->assertStringContainsString('accept="image/png,image/jpeg"', $html);
        $this->assertStringContainsString('multiple', $html);
        $this->assertStringContainsString('disabled', $html);
        $this->assertStringContainsString('name="asset[]"', $html);
        $this->assertStringContainsString('PNG or JPEG, up to 5 MB', $html);
        $this->assertStringNotContainsString('data-upload', $html);
        $this->assertStringNotContainsString('data-preview', $html);
    }

    public function test_file_input_merges_caller_and_field_descriptions_without_duplicate_attributes(): void
    {
        $html = Blade::render('<x-ui.form.file-input id="asset" name="asset" label="Asset" hint="PNG only" error="Invalid" aria-describedby="asset-guidance" />');

        $this->assertSame(1, substr_count($html, 'aria-describedby='));
        $this->assertStringContainsString('aria-describedby="asset-guidance asset-help asset-error"', $html);
    }
}

<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class DiffViewerTest extends TestCase
{
    public function test_diff_viewer_renders_before_and_after_values_with_change_indicator(): void
    {
        $html = Blade::render(
            '<x-admin.diff-viewer field-label="Refresh rate" before-label="Source A" before-value="60 Hz" after-label="Source B" after-value="120 Hz" />'
        );

        $this->assertStringContainsString('Refresh rate', $html);
        $this->assertStringContainsString('Source A', $html);
        $this->assertStringContainsString('60 Hz', $html);
        $this->assertStringContainsString('Source B', $html);
        $this->assertStringContainsString('120 Hz', $html);
        $this->assertStringContainsString('data-admin-diff-state="changed"', $html);
        $this->assertStringContainsString('bg-admin-warning-soft', $html);
    }

    public function test_diff_viewer_distinguishes_added_removed_and_side_by_side_states(): void
    {
        $added = Blade::render(
            '<x-admin.diff-viewer before-value="" after-value="New value" variant="side-by-side" />'
        );
        $removed = Blade::render(
            '<x-admin.diff-viewer before-value="Old value" after-value="" />'
        );

        $this->assertStringContainsString('data-admin-diff-state="added"', $added);
        $this->assertStringContainsString('md:grid-cols-2', $added);
        $this->assertStringContainsString('bg-admin-success-soft', $added);
        $this->assertStringContainsString('data-admin-diff-state="removed"', $removed);
        $this->assertStringContainsString('bg-admin-danger-soft', $removed);
    }

    public function test_diff_viewer_renders_json_as_preformatted_text_and_escapes_values(): void
    {
        $html = Blade::render(
            '<x-admin.diff-viewer :before-value="$before" :after-value="$after" />',
            [
                'before' => '{"unsafe":"<old>"}',
                'after' => '{"unsafe":"<new>"}',
            ]
        );

        $this->assertStringContainsString('<pre', $html);
        $this->assertStringContainsString('&lt;old&gt;', $html);
        $this->assertStringContainsString('&lt;new&gt;', $html);
        $this->assertStringNotContainsString('<old>', $html);
    }
}

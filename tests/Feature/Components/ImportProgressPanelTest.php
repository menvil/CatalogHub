<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ImportProgressPanelTest extends TestCase
{
    public function test_import_progress_panel_renders_import_steps_counts_and_current_status(): void
    {
        $steps = [
            ['key' => 'raw', 'label' => 'Raw import', 'status' => 'completed'],
            ['key' => 'mapping', 'label' => 'Mapping', 'status' => 'current'],
            ['key' => 'review', 'label' => 'Review', 'status' => 'pending'],
        ];
        $stats = [
            ['label' => 'raw products', 'value' => 120],
            ['label' => 'needs review', 'value' => 18],
        ];

        $html = Blade::render(
            '<x-admin.import-progress-panel source-name="Vendor feed" category-name="Coffee machines" status="running" :steps="$steps" :stats="$stats" />',
            compact('steps', 'stats')
        );

        $this->assertStringContainsString('data-admin-import-progress-panel', $html);
        $this->assertStringContainsString('Vendor feed', $html);
        $this->assertStringContainsString('Coffee machines', $html);
        $this->assertStringContainsString('running', $html);
        $this->assertStringContainsString('Raw import', $html);
        $this->assertStringContainsString('Mapping', $html);
        $this->assertStringContainsString('Review', $html);
        $this->assertStringContainsString('raw products', $html);
        $this->assertStringContainsString('120', $html);
        $this->assertStringContainsString('needs review', $html);
        $this->assertStringContainsString('18', $html);
    }

    public function test_import_progress_panel_maps_failed_and_completed_status_variants(): void
    {
        $failed = Blade::render('<x-admin.import-progress-panel source-name="Feed" status="failed" />');
        $error = Blade::render('<x-admin.import-progress-panel source-name="Feed" status="error" />');
        $completed = Blade::render('<x-admin.import-progress-panel source-name="Feed" status="completed" />');

        $this->assertStringContainsString('data-admin-status-badge="danger"', $failed);
        $this->assertStringContainsString('data-admin-status-badge="danger"', $error);
        $this->assertStringContainsString('data-admin-status-badge="success"', $completed);
    }

    public function test_import_progress_panel_escapes_labels(): void
    {
        $html = Blade::render('<x-admin.import-progress-panel source-name="<Feed>" category-name="<Category>" status="<running>" />');

        $this->assertStringContainsString('&lt;Feed&gt;', $html);
        $this->assertStringContainsString('&lt;Category&gt;', $html);
        $this->assertStringContainsString('&lt;running&gt;', $html);
        $this->assertStringNotContainsString('<Feed>', $html);
    }
}

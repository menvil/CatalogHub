<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ConflictReviewCardTest extends TestCase
{
    public function test_conflict_review_card_renders_conflict_title_source_values_and_actions(): void
    {
        $actions = [
            ['label' => 'Accept source A'],
            ['label' => 'Accept source B'],
        ];

        $html = Blade::render(
            '<x-admin.conflict-review-card title="Refresh rate conflict" entity-label="Product #123" field-label="refresh_rate" source-a="Vendor feed" source-b="Central override" value-a="60 Hz" value-b="120 Hz" severity="high" :actions="$actions" />',
            compact('actions')
        );

        $this->assertStringContainsString('data-admin-conflict-review-card', $html);
        $this->assertStringContainsString('Refresh rate conflict', $html);
        $this->assertStringContainsString('Product #123', $html);
        $this->assertStringContainsString('refresh_rate', $html);
        $this->assertStringContainsString('Vendor feed', $html);
        $this->assertStringContainsString('Central override', $html);
        $this->assertStringContainsString('60 Hz', $html);
        $this->assertStringContainsString('120 Hz', $html);
        $this->assertStringContainsString('data-admin-status-badge="danger"', $html);
        $this->assertStringContainsString('Accept source A', $html);
        $this->assertStringContainsString('Accept source B', $html);
    }

    public function test_conflict_review_card_maps_medium_and_low_severity(): void
    {
        $medium = Blade::render('<x-admin.conflict-review-card title="Conflict" entity-label="Entity" field-label="field" source-a="A" source-b="B" value-a="1" value-b="2" severity="medium" />');
        $low = Blade::render('<x-admin.conflict-review-card title="Conflict" entity-label="Entity" field-label="field" source-a="A" source-b="B" value-a="1" value-b="2" severity="low" />');

        $this->assertStringContainsString('data-admin-status-badge="warning"', $medium);
        $this->assertStringContainsString('data-admin-status-badge="info"', $low);
    }

    public function test_conflict_review_card_escapes_values(): void
    {
        $html = Blade::render('<x-admin.conflict-review-card title="<Conflict>" entity-label="<Entity>" field-label="<Field>" source-a="<A>" source-b="<B>" value-a="<1>" value-b="<2>" />');

        $this->assertStringContainsString('&lt;Conflict&gt;', $html);
        $this->assertStringContainsString('&lt;Entity&gt;', $html);
        $this->assertStringContainsString('&lt;Field&gt;', $html);
        $this->assertStringContainsString('&lt;1&gt;', $html);
        $this->assertStringNotContainsString('<Conflict>', $html);
    }
}

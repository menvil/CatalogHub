<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ChangeRequestCardTest extends TestCase
{
    public function test_change_request_card_renders_requester_proposed_change_status_and_actions(): void
    {
        $actions = [
            ['label' => 'Approve'],
            ['label' => 'Reject'],
        ];

        $html = Blade::render(
            '<x-admin.change-request-card request-title="Correct product title" requester-label="Site editor" source-site-label="DE portal" entity-label="Product #123" field-label="title" current-value="Old title" proposed-value="New title" status="pending" submitted-at="2026-07-08" :actions="$actions" />',
            compact('actions')
        );

        $this->assertStringContainsString('data-admin-change-request-card', $html);
        $this->assertStringContainsString('Correct product title', $html);
        $this->assertStringContainsString('Site editor', $html);
        $this->assertStringContainsString('DE portal', $html);
        $this->assertStringContainsString('Product #123', $html);
        $this->assertStringContainsString('title', $html);
        $this->assertStringContainsString('Old title', $html);
        $this->assertStringContainsString('New title', $html);
        $this->assertStringContainsString('pending', $html);
        $this->assertStringContainsString('2026-07-08', $html);
        $this->assertStringContainsString('Approve', $html);
        $this->assertStringContainsString('Reject', $html);
    }

    public function test_change_request_card_maps_status_variants(): void
    {
        $approved = Blade::render('<x-admin.change-request-card request-title="Request" requester-label="User" entity-label="Entity" field-label="field" current-value="A" proposed-value="B" status="approved" />');
        $rejected = Blade::render('<x-admin.change-request-card request-title="Request" requester-label="User" entity-label="Entity" field-label="field" current-value="A" proposed-value="B" status="rejected" />');
        $needsInfo = Blade::render('<x-admin.change-request-card request-title="Request" requester-label="User" entity-label="Entity" field-label="field" current-value="A" proposed-value="B" status="needs_info" />');

        $this->assertStringContainsString('data-admin-status-badge="success"', $approved);
        $this->assertStringContainsString('data-admin-status-badge="danger"', $rejected);
        $this->assertStringContainsString('data-admin-status-badge="warning"', $needsInfo);
    }

    public function test_change_request_card_escapes_values(): void
    {
        $html = Blade::render('<x-admin.change-request-card request-title="<Request>" requester-label="<User>" entity-label="<Entity>" field-label="<Field>" current-value="<A>" proposed-value="<B>" />');

        $this->assertStringContainsString('&lt;Request&gt;', $html);
        $this->assertStringContainsString('&lt;User&gt;', $html);
        $this->assertStringContainsString('&lt;Entity&gt;', $html);
        $this->assertStringContainsString('&lt;Field&gt;', $html);
        $this->assertStringContainsString('&lt;A&gt;', $html);
        $this->assertStringContainsString('&lt;B&gt;', $html);
        $this->assertStringNotContainsString('<Request>', $html);
    }
}

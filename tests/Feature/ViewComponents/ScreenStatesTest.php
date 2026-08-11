<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class ScreenStatesTest extends TestCase
{
    public function test_initial_and_filtered_empty_states_have_distinct_truthful_actions(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.states.empty title="No brands yet" message="Create the first brand when source data is ready." action-label="Create brand" action-url="/admin/central/brands/create" />
            <x-ui.states.filtered-empty title="No matching brands" message="No brands match the current filters." clear-label="Clear filters" clear-url="/admin/central/brands?q=" />
        BLADE);

        $this->assertStringContainsString('data-ui-screen-state="empty"', $html);
        $this->assertStringContainsString('data-ui-screen-state="filtered-empty"', $html);
        $this->assertStringContainsString('href="/admin/central/brands/create"', $html);
        $this->assertStringContainsString('href="/admin/central/brands?q="', $html);
        $this->assertStringNotContainsString('Create brand</a>', substr($html, strpos($html, 'data-ui-screen-state="filtered-empty"')));
    }

    public function test_empty_states_escape_long_content_and_keep_heading_semantics(): void
    {
        $message = str_repeat('A long deterministic explanation ', 8).'<script>alert(1)</script>';
        $html = Blade::render(
            '<x-ui.states.empty title="Nothing configured" :message="$message" />',
            ['message' => $message],
        );

        $this->assertMatchesRegularExpression('/<h3[^>]*>Nothing configured<\/h3>/', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert(1)</script>', $html);
    }

    public function test_empty_states_generate_unique_ids_and_preserve_caller_ids(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.states.empty title="First" message="First message" />
            <x-ui.states.empty id="custom-empty" title="Second" message="Second message" />
            <x-ui.states.filtered-empty title="Third" message="Third message" clear-url="/clear" />
        BLADE);

        preg_match_all('/<section[^>]*\sid="([^"]+)"[^>]*aria-labelledby="([^"]+)"/', $html, $matches);

        $this->assertCount(3, $matches[1]);
        $this->assertCount(3, array_unique($matches[1]));
        $this->assertSame('custom-empty', $matches[1][1]);
        foreach ($matches[1] as $index => $id) {
            $this->assertSame($id.'-title', $matches[2][$index]);
            $this->assertStringContainsString('id="'.$id.'-title"', $html);
        }
    }

    public function test_loading_state_is_accessible_motion_safe_and_has_no_fake_data(): void
    {
        $html = Blade::render('<x-ui.states.loading label="Loading brands" :rows="4" />');

        $this->assertStringContainsString('data-ui-screen-state="loading"', $html);
        $this->assertStringContainsString('role="status"', $html);
        $this->assertStringContainsString('aria-busy="true"', $html);
        $this->assertStringContainsString('motion-reduce:animate-none', $html);
        $this->assertSame(4, substr_count($html, 'data-ui-loading-row'));
        $this->assertStringNotContainsString('%', $html);
    }
}

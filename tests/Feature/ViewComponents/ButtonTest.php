<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class ButtonTest extends TestCase
{
    public function test_button_variants_render_semantic_button_states(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.button variant="primary" type="submit">Save</x-ui.button>
            <x-ui.button variant="secondary">Cancel</x-ui.button>
            <x-ui.button variant="tertiary" disabled>Unavailable</x-ui.button>
            <x-ui.button variant="danger" loading>Delete</x-ui.button>
        BLADE);

        $this->assertStringContainsString('data-ui-button="primary"', $html);
        $this->assertStringContainsString('type="submit"', $html);
        $this->assertStringContainsString('data-ui-button="danger"', $html);
        $this->assertStringContainsString('aria-busy="true"', $html);
        $this->assertSame(2, preg_match_all('/<button[^>]*\sdisabled(?:\s|>)/', $html));
        $this->assertSame(4, substr_count($html, '<button'));
    }

    public function test_link_button_and_action_group_never_nest_interactive_elements(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-ui.action-group label="Record actions">
                <x-ui.button href="/admin/central" variant="secondary">Open</x-ui.button>
                <x-ui.button disabled href="/unsafe">Disabled link</x-ui.button>
            </x-ui.action-group>
        BLADE);

        $this->assertStringContainsString('aria-label="Record actions"', $html);
        $this->assertStringContainsString('href="/admin/central"', $html);
        $this->assertStringContainsString('data-ui-disabled-link', $html);
        $this->assertSame(1, substr_count($html, '<a'));
        $this->assertStringNotContainsString('<button', $html);
        $this->assertStringNotContainsString('<a href="/unsafe"', $html);
    }

    public function test_button_labels_are_escaped(): void
    {
        $html = Blade::render('<x-ui.button :label="$label" />', [
            'label' => '<script>alert(1)</script>',
        ]);

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }
}

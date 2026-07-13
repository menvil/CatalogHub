<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AdminCardTest extends TestCase
{
    public function test_admin_card_renders_title_actions_and_content_slot(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-admin.card title="Catalog quality" description="Review warnings">
                <x-slot:actions>
                    <button type="button">Refresh</button>
                </x-slot:actions>

                <p>Card content</p>

                <x-slot:footer>
                    <span>Footer metadata</span>
                </x-slot:footer>
            </x-admin.card>
        BLADE);

        $this->assertStringContainsString('Catalog quality', $html);
        $this->assertStringContainsString('Review warnings', $html);
        $this->assertStringContainsString('Refresh', $html);
        $this->assertStringContainsString('Card content', $html);
        $this->assertStringContainsString('Footer metadata', $html);
        $this->assertStringContainsString('data-admin-card="default"', $html);
    }

    public function test_admin_card_supports_custom_header_padding_and_variants(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-admin.card variant="danger" padding="lg">
                <x-slot:header>
                    <h2>Danger header</h2>
                </x-slot:header>

                Danger content
            </x-admin.card>
        BLADE);

        $this->assertStringContainsString('Danger header', $html);
        $this->assertStringContainsString('Danger content', $html);
        $this->assertStringContainsString('data-admin-card="danger"', $html);
        $this->assertStringContainsString('border-admin-danger/30', $html);
        $this->assertStringContainsString('p-6', $html);
    }

    public function test_admin_card_supports_success_variant(): void
    {
        $html = Blade::render('<x-admin.card variant="success">Imported</x-admin.card>');

        $this->assertStringContainsString('data-admin-card="success"', $html);
        $this->assertStringContainsString('border-admin-success/30', $html);
        $this->assertStringContainsString('bg-admin-success-soft', $html);
    }

    public function test_admin_card_escapes_title_and_description(): void
    {
        $html = Blade::render(
            '<x-admin.card title="<Title>" description="<Description>">Body</x-admin.card>'
        );

        $this->assertStringContainsString('&lt;Title&gt;', $html);
        $this->assertStringContainsString('&lt;Description&gt;', $html);
        $this->assertStringNotContainsString('<Title>', $html);
    }
}

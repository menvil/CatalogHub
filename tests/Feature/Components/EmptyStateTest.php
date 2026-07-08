<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class EmptyStateTest extends TestCase
{
    public function test_empty_state_renders_title_description_and_optional_action(): void
    {
        $html = Blade::render(<<<'BLADE'
            <x-admin.empty-state title="No products found" description="Try adjusting filters." icon="0">
                <x-slot:action>
                    <button type="button">Reset filters</button>
                </x-slot:action>
            </x-admin.empty-state>
        BLADE);

        $this->assertStringContainsString('No products found', $html);
        $this->assertStringContainsString('Try adjusting filters.', $html);
        $this->assertStringContainsString('Reset filters', $html);
        $this->assertStringContainsString('data-admin-empty-state="default"', $html);
        $this->assertStringContainsString('aria-hidden="true"', $html);
    }

    public function test_empty_state_supports_warning_and_error_variants(): void
    {
        foreach ([
            'warning' => 'bg-admin-warning-soft',
            'error' => 'bg-admin-danger-soft',
        ] as $variant => $expectedClass) {
            $html = Blade::render(
                '<x-admin.empty-state title="No stale projections" icon="!" variant="'.$variant.'" />'
            );

            $this->assertStringContainsString('No stale projections', $html);
            $this->assertStringContainsString('data-admin-empty-state="'.$variant.'"', $html);
            $this->assertStringContainsString($expectedClass, $html);
        }
    }

    public function test_empty_state_escapes_text_props(): void
    {
        $html = Blade::render(
            '<x-admin.empty-state title="<No imports>" description="<No domain data>" />'
        );

        $this->assertStringContainsString('&lt;No imports&gt;', $html);
        $this->assertStringContainsString('&lt;No domain data&gt;', $html);
        $this->assertStringNotContainsString('<No imports>', $html);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class FlashMessagesTest extends TestCase
{
    public function test_flash_region_renders_each_supported_tone(): void
    {
        foreach (['success', 'warning', 'error'] as $tone) {
            session()->flash($tone, ucfirst($tone).' message');
        }

        $html = Blade::render('<x-admin.flash-messages />');

        $this->assertStringContainsString('data-admin-flash-region', $html);
        $this->assertStringContainsString('aria-live="polite"', $html);

        foreach (['success', 'warning', 'error'] as $tone) {
            $this->assertStringContainsString('data-admin-flash="'.$tone.'"', $html);
            $this->assertStringContainsString(ucfirst($tone).' message', $html);
        }
    }

    public function test_flash_messages_are_escaped_and_empty_region_is_omitted(): void
    {
        session()->flash('error', '<script>alert("unsafe")</script>');

        $html = Blade::render('<x-admin.flash-messages />');

        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);

        session()->forget(['success', 'warning', 'error']);

        $this->assertStringNotContainsString(
            'data-admin-flash-region',
            Blade::render('<x-admin.flash-messages />'),
        );
    }
}

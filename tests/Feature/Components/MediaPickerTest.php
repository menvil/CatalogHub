<?php

namespace Tests\Feature\Components;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class MediaPickerTest extends TestCase
{
    public function test_media_picker_renders_empty_state_and_placeholder_actions(): void
    {
        $html = Blade::render(
            '<x-admin.media-picker empty-title="No gallery images" empty-description="Select product images later." />'
        );

        $this->assertStringContainsString('data-admin-media-picker="single"', $html);
        $this->assertStringContainsString('No gallery images', $html);
        $this->assertStringContainsString('Select product images later.', $html);
        $this->assertStringContainsString('Choose', $html);
        $this->assertStringContainsString('Upload', $html);
        $this->assertStringContainsString('disabled', $html);
    }

    public function test_media_picker_renders_selected_media_preview(): void
    {
        $selectedItems = [
            ['name' => 'Main image', 'type' => 'image/png', 'url' => '/demo/main.png'],
            ['name' => 'Spec sheet', 'type' => 'application/pdf'],
        ];

        $html = Blade::render(
            '<x-admin.media-picker mode="multiple" :selected-items="$selectedItems" :accepted-types="[\'image/png\', \'image/jpeg\']" />',
            compact('selectedItems')
        );

        $this->assertStringContainsString('data-admin-media-picker="multiple"', $html);
        $this->assertStringContainsString('Multiple selection', $html);
        $this->assertStringContainsString('image/png, image/jpeg', $html);
        $this->assertStringContainsString('Main image', $html);
        $this->assertStringContainsString('/demo/main.png', $html);
        $this->assertStringContainsString('Spec sheet', $html);
    }

    public function test_media_picker_escapes_demo_item_text(): void
    {
        $selectedItems = [
            ['name' => '<Unsafe>', 'type' => '<image>'],
        ];

        $html = Blade::render('<x-admin.media-picker :selected-items="$selectedItems" />', compact('selectedItems'));

        $this->assertStringContainsString('&lt;Unsafe&gt;', $html);
        $this->assertStringContainsString('&lt;image&gt;', $html);
        $this->assertStringNotContainsString('<Unsafe>', $html);
    }
}

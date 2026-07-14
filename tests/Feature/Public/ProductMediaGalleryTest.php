<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ProductMediaGalleryTest extends TestCase
{
    public function test_gallery_renders_resolved_main_image_thumbnails_and_safe_alt_text(): void
    {
        $html = Blade::render(
            "@include('public.components.product-media-gallery', ['media' => \$media])",
            ['media' => [
                'main' => ['url' => 'https://cdn.test/main.jpg', 'alt' => 'Aurora main image', 'width' => 800, 'height' => 600],
                'gallery' => [
                    ['url' => 'https://cdn.test/side.jpg', 'alt' => 'Aurora side view'],
                    ['url' => 'https://cdn.test/back.jpg', 'alt' => 'Aurora back view'],
                ],
            ]],
        );

        $this->assertStringContainsString('src="https://cdn.test/main.jpg"', $html);
        $this->assertStringContainsString('alt="Aurora main image"', $html);
        $this->assertStringContainsString('src="https://cdn.test/side.jpg"', $html);
        $this->assertStringContainsString('src="https://cdn.test/back.jpg"', $html);
    }

    public function test_gallery_renders_a_placeholder_for_missing_media(): void
    {
        $html = Blade::render("@include('public.components.product-media-gallery', ['media' => []])");

        $this->assertStringContainsString('data-media-placeholder', $html);
        $this->assertStringNotContainsString('<img', $html);
    }
}

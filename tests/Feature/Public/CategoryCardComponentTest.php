<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CategoryCardComponentTest extends TestCase
{
    public function test_category_card_renders_title_description_image_and_localized_link(): void
    {
        $html = Blade::render(
            "@include('public.components.category-card', ['category' => \$category])",
            ['category' => [
                'title' => 'Monitors',
                'description' => 'Compare desktop displays.',
                'image' => ['url' => 'https://cdn.test/monitors.jpg', 'alt' => 'Monitor category'],
                'url' => 'https://catalog.test/en-US/categories/monitors',
            ]],
        );

        $this->assertStringContainsString('data-category-card', $html);
        $this->assertStringContainsString('Monitors', $html);
        $this->assertStringContainsString('Compare desktop displays.', $html);
        $this->assertStringContainsString('src="https://cdn.test/monitors.jpg"', $html);
        $this->assertStringContainsString('href="https://catalog.test/en-US/categories/monitors"', $html);
    }

    public function test_category_card_handles_missing_image(): void
    {
        $html = Blade::render(
            "@include('public.components.category-card', ['category' => ['title' => 'Keyboards', 'url' => '/keyboards']])",
        );

        $this->assertStringContainsString('Keyboards', $html);
        $this->assertStringContainsString('data-category-card-placeholder', $html);
        $this->assertStringNotContainsString('<img', $html);
    }
}

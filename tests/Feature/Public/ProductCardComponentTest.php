<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ProductCardComponentTest extends TestCase
{
    public function test_product_card_renders_projection_fields_and_list_variant(): void
    {
        $html = Blade::render(
            "@include('public.components.product-card', ['product' => \$product, 'variant' => 'list'])",
            ['product' => [
                'title' => 'Aurora 27 Pro',
                'url' => 'https://catalog.test/en-US/products/aurora-27-pro',
                'media' => ['main' => ['url' => 'https://cdn.test/aurora.jpg', 'alt' => 'Aurora monitor']],
                'summary' => ['key_specs' => ['4K UHD', '165 Hz'], 'rating' => ['value' => 4.7, 'review_count' => 128]],
                'price_placeholder' => 'Offers coming soon',
            ]],
        );

        $this->assertStringContainsString('data-product-card', $html);
        $this->assertStringContainsString('data-variant="list"', $html);
        $this->assertStringContainsString('href="https://catalog.test/en-US/products/aurora-27-pro"', $html);
        $this->assertStringContainsString('src="https://cdn.test/aurora.jpg"', $html);
        $this->assertStringContainsString('4K UHD', $html);
        $this->assertStringContainsString('4.7', $html);
        $this->assertStringContainsString('Offers coming soon', $html);
    }

    public function test_product_card_handles_missing_optional_media_specs_and_rating(): void
    {
        $html = Blade::render(
            "@include('public.components.product-card', ['product' => ['title' => 'Simple Product', 'url' => '/product'], 'variant' => 'grid'])",
        );

        $this->assertStringContainsString('Simple Product', $html);
        $this->assertStringContainsString('data-product-card-placeholder', $html);
        $this->assertStringNotContainsString('<img', $html);
    }
}

<?php

namespace Tests\Feature\Public;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ProductRatingComponentTest extends TestCase
{
    public function test_rating_block_renders_projection_rating_and_review_count(): void
    {
        $html = Blade::render(
            "@include('public.components.product-rating', ['rating' => \$rating])",
            ['rating' => ['value' => 4.7, 'review_count' => 128]],
        );

        $this->assertStringContainsString('4.7', $html);
        $this->assertStringContainsString('128 reviews', $html);
        $this->assertStringContainsString('aria-label="Rated 4.7 out of 5"', $html);
    }

    public function test_rating_block_has_a_neutral_state_when_rating_is_missing(): void
    {
        $html = Blade::render("@include('public.components.product-rating', ['rating' => null])");

        $this->assertStringContainsString('Not rated yet', $html);
        $this->assertStringNotContainsString('reviews backend', strtolower($html));
    }
}

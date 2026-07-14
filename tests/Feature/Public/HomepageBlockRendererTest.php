<?php

namespace Tests\Feature\Public;

use App\Models\Site;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageBlockRendererTest extends TestCase
{
    use RefreshDatabase;

    public function test_enabled_blocks_render_in_configured_order_and_disabled_blocks_are_skipped(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        $site->homeBlocks()->where('block_code', 'popular_categories')->update(['enabled' => false]);

        $response = $this->get('http://tech-compare.test/en-US')->assertOk();
        $content = $response->getContent();

        $this->assertStringContainsString('data-theme-block="hero_search"', $content);
        $this->assertStringNotContainsString('data-theme-block="popular_categories"', $content);
        $this->assertStringContainsString('data-theme-block="top_products"', $content);
        $this->assertLessThan(
            strpos($content, 'data-theme-block="top_products"'),
            strpos($content, 'data-theme-block="hero_search"'),
        );
    }
}

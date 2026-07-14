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

        $this->get('http://tech-compare.test/en-US')
            ->assertOk()
            ->assertSee('data-theme-block="hero_search"', false)
            ->assertDontSee('data-theme-block="popular_categories"', false)
            ->assertSee('data-theme-block="top_products"', false)
            ->assertSeeInOrder([
                'data-theme-block="hero_search"',
                'data-theme-block="top_products"',
            ], false);
    }
}

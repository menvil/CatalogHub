<?php

namespace Tests\Feature\Public;

use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_site_home_resolves_site_locale_theme_and_page_structure(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);

        $this->get('http://tech-compare.test/en-US')
            ->assertOk()
            ->assertSee('Tech Compare Global')
            ->assertSee('Find the right technology')
            ->assertSee('data-homepage-blocks', false);
    }

    public function test_home_returns_not_found_for_unknown_site_or_disabled_locale(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);

        $this->get('http://unknown.test/en-US')->assertNotFound();
        $this->get('http://tech-compare.test/de-DE')->assertNotFound();
    }
}

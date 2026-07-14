<?php

namespace Tests\Feature\Public;

use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleStubPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_article_route_renders_the_public_article_layout(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);

        $this->get('http://tech-compare.test/en-US/articles/how-to-choose-a-monitor')
            ->assertOk()
            ->assertSee('How to choose a monitor')
            ->assertSee('Article preview')
            ->assertSee('Related products are coming soon');
    }

    public function test_unknown_demo_article_returns_not_found(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);

        $this->get('http://tech-compare.test/en-US/articles/missing')->assertNotFound();
    }
}

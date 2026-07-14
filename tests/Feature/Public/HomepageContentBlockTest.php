<?php

namespace Tests\Feature\Public;

use App\Enums\ContentType;
use App\Models\ContentItem;
use App\Models\ContentTranslation;
use App\Models\Site;
use App\Models\SiteHomeBlock;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomepageContentBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_content_block_filters_published_content_by_site_locale_and_type(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        SiteHomeBlock::query()->create([
            'site_id' => $site->id,
            'block_code' => 'content_block',
            'position' => 30,
            'enabled' => true,
            'config_json' => [
                'title' => 'Fresh buying guides',
                'content_types' => [ContentType::BuyingGuide->value],
                'limit' => 1,
                'show_excerpt' => true,
            ],
        ]);

        $guide = ContentItem::factory()->published()->for($site)->create([
            'type' => ContentType::BuyingGuide,
            'published_at' => now()->subDay(),
        ]);
        ContentTranslation::factory()->published()->for($guide)->create([
            'locale' => 'en-US',
            'title' => 'Buying the right monitor',
            'slug' => 'buying-the-right-monitor',
            'excerpt' => 'A useful buying guide excerpt.',
        ]);
        $article = ContentItem::factory()->published()->for($site)->create([
            'type' => ContentType::Article,
        ]);
        ContentTranslation::factory()->published()->for($article)->create([
            'locale' => 'en-US',
            'title' => 'Filtered article',
            'slug' => 'filtered-article',
        ]);
        $draft = ContentItem::factory()->draft()->for($site)->create([
            'type' => ContentType::BuyingGuide,
        ]);
        ContentTranslation::factory()->published()->for($draft)->create([
            'locale' => 'en-US',
            'title' => 'Draft guide',
            'slug' => 'draft-guide',
        ]);

        $this->get('http://tech-compare.test/en-US')
            ->assertOk()
            ->assertSee('data-theme-block="content_block"', false)
            ->assertSee('Fresh buying guides')
            ->assertSee('Buying the right monitor')
            ->assertSee('A useful buying guide excerpt.')
            ->assertSee('https://tech-compare.test/en-US/articles/buying-the-right-monitor', false)
            ->assertDontSee('Filtered article')
            ->assertDontSee('Draft guide');
    }

    public function test_empty_content_block_does_not_break_or_clutter_homepage(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        SiteHomeBlock::query()->create([
            'site_id' => $site->id,
            'block_code' => 'content_block',
            'position' => 30,
            'enabled' => true,
            'config_json' => ['title' => 'No content yet'],
        ]);

        $this->get('http://tech-compare.test/en-US')
            ->assertOk()
            ->assertDontSee('No content yet');
    }
}

<?php

namespace Tests\Feature\Public;

use App\Enums\ContentType;
use App\Enums\SiteStatus;
use App\Models\ContentItem;
use App\Models\ContentTranslation;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\EnablesSiteLocales;
use Tests\TestCase;

class PublicContentPageTest extends TestCase
{
    use EnablesSiteLocales;
    use RefreshDatabase;

    public function test_published_article_is_scoped_by_site_locale_and_renders_seo(): void
    {
        $site = $this->site('content.test', ['en-US']);
        $item = ContentItem::factory()->published()->for($site)->create([
            'type' => ContentType::Article,
        ]);
        ContentTranslation::factory()->published()->for($item)->create([
            'locale' => 'en-US',
            'slug' => 'best-monitors',
            'title' => 'Best Monitors',
            'excerpt' => 'A practical guide.',
            'body' => "First paragraph.\n\nSecond paragraph.",
            'meta_title' => 'Best Monitors 2026',
            'meta_description' => 'Compare current monitor picks.',
            'og_title' => 'Monitor guide',
            'og_description' => 'Our monitor picks.',
        ]);

        $this->get('http://content.test/en-US/articles/best-monitors')
            ->assertOk()
            ->assertSee('Best Monitors')
            ->assertSee('First paragraph.')
            ->assertSee('Second paragraph.')
            ->assertSee('<title>Best Monitors 2026</title>', false)
            ->assertSee('<meta name="description" content="Compare current monitor picks.">', false)
            ->assertSee('<meta property="og:title" content="Monitor guide">', false)
            ->assertSee('<link rel="canonical" href="http://content.test/en-US/articles/best-monitors">', false);
    }

    public function test_draft_item_and_draft_translation_are_not_public(): void
    {
        $site = $this->site('draft-content.test', ['en-US']);
        $draftItem = ContentItem::factory()->draft()->for($site)->create();
        ContentTranslation::factory()->published()->for($draftItem)->create([
            'locale' => 'en-US',
            'slug' => 'draft-item',
        ]);

        $publishedItem = ContentItem::factory()->published()->for($site)->create();
        ContentTranslation::factory()->for($publishedItem)->create([
            'locale' => 'en-US',
            'slug' => 'draft-translation',
            'status' => 'draft',
        ]);

        $this->get('http://draft-content.test/en-US/articles/draft-item')->assertNotFound();
        $this->get('http://draft-content.test/en-US/articles/draft-translation')->assertNotFound();
    }

    public function test_slug_does_not_resolve_for_wrong_site_or_locale(): void
    {
        $site = $this->site('right-content.test', ['en-US', 'de-DE']);
        $otherSite = $this->site('other-content.test', ['en-GB']);
        $item = ContentItem::factory()->published()->for($site)->create();
        ContentTranslation::factory()->published()->for($item)->create([
            'locale' => 'en-US',
            'slug' => 'site-only',
        ]);

        $this->get('http://right-content.test/de-DE/articles/site-only')->assertNotFound();
        $this->get('http://other-content.test/en-GB/articles/site-only')->assertNotFound();

        $this->assertNotSame($site->id, $otherSite->id);
    }

    public function test_published_faq_renders_structured_questions_and_answers(): void
    {
        $site = $this->site('faq-content.test', ['en-US']);
        $item = ContentItem::factory()->published()->for($site)->create([
            'type' => ContentType::Faq,
        ]);
        ContentTranslation::factory()->published()->for($item)->create([
            'locale' => 'en-US',
            'slug' => 'monitor-care',
            'title' => 'Monitor care',
            'body' => null,
            'body_json' => [
                ['question' => 'How should I clean it?', 'answer' => 'Use a microfiber cloth.', 'position' => 0],
            ],
        ]);

        $this->get('http://faq-content.test/en-US/articles/monitor-care')
            ->assertOk()
            ->assertSee('How should I clean it?')
            ->assertSee('Use a microfiber cloth.');
    }

    /** @param list<string> $locales */
    private function site(string $domain, array $locales): Site
    {
        $site = Site::factory()->create([
            'domain' => $domain,
            'default_locale' => $locales[0],
            'status' => SiteStatus::Active,
        ]);

        foreach ($locales as $locale) {
            $this->enableLocale($site, $locale);
        }

        return $site;
    }
}

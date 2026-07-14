<?php

namespace Tests\Feature\Public;

use App\Enums\ContentRelationTargetType;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ContentItem;
use App\Models\ContentRelation;
use App\Models\ContentTranslation;
use App\Models\Site;
use App\Services\Content\RelatedContentResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RelatedContentBlockTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolver_returns_only_published_content_for_site_locale_in_relation_order(): void
    {
        $site = Site::factory()->create(['domain' => 'related.test']);
        $otherSite = Site::factory()->create();
        $product = CentralProduct::factory()->create();

        $second = $this->relatedItem($site, $product, 'Second guide', 'second-guide', 20);
        $first = $this->relatedItem($site, $product, 'First guide', 'first-guide', 10);
        $draft = ContentItem::factory()->draft()->for($site)->create();
        ContentTranslation::factory()->published()->for($draft)->create([
            'locale' => 'en-US',
            'title' => 'Draft guide',
            'slug' => 'draft-guide',
        ]);
        ContentRelation::factory()->for($draft)->product($product)->create();
        $wrongLocale = $this->relatedItem($site, $product, 'German guide', 'german-guide', 0, 'de-DE');
        $wrongSite = $this->relatedItem($otherSite, $product, 'Other site guide', 'other-guide', 0);

        $items = app(RelatedContentResolver::class)->resolve(
            site: $site,
            locale: 'en-US',
            relatedType: ContentRelationTargetType::Product,
            relatedId: $product->id,
            limit: 4,
        );

        $this->assertSame(['First guide', 'Second guide'], $items->pluck('title')->all());
        $this->assertSame('https://related.test/en-US/articles/first-guide', $items->first()->url);
        $this->assertFalse($items->contains('title', $draft->translations()->first()->title));
        $this->assertFalse($items->contains('title', $wrongLocale->translations()->first()->title));
        $this->assertFalse($items->contains('title', $wrongSite->translations()->first()->title));
        $this->assertTrue($items->contains('title', $first->translations()->first()->title));
        $this->assertTrue($items->contains('title', $second->translations()->first()->title));
    }

    public function test_related_content_component_renders_cards_and_hides_empty_state(): void
    {
        $site = Site::factory()->create(['domain' => 'cards.test']);
        $product = CentralProduct::factory()->create();
        $this->relatedItem($site, $product, 'Monitor guide', 'monitor-guide', 0);
        $items = app(RelatedContentResolver::class)->resolve(
            $site,
            'en-US',
            ContentRelationTargetType::Product,
            $product->id,
        );

        $this->blade("@include('public.components.related-content', ['items' => \$items])", compact('items'))
            ->assertSee('Related guides and articles')
            ->assertSee('Monitor guide')
            ->assertSee('https://cards.test/en-US/articles/monitor-guide', false);

        $this->blade("@include('public.components.related-content', ['items' => \$items])", ['items' => collect()])
            ->assertDontSee('Related guides and articles');
    }

    private function relatedItem(
        Site $site,
        CentralProduct $product,
        string $title,
        string $slug,
        int $position,
        string $locale = 'en-US',
    ): ContentItem {
        $item = ContentItem::factory()->published()->for($site)->create();
        ContentTranslation::factory()->published()->for($item)->create([
            'locale' => $locale,
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $title.' excerpt.',
        ]);
        ContentRelation::factory()->for($item)->product($product)->create([
            'position' => $position,
        ]);

        return $item;
    }
}

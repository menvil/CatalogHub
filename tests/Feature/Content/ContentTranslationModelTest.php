<?php

namespace Tests\Feature\Content;

use App\Models\ContentItem;
use App\Models\ContentTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentTranslationModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_translation_factory_relationship_and_json_cast_work(): void
    {
        $item = ContentItem::factory()->create();
        $translation = ContentTranslation::factory()->create([
            'content_item_id' => $item->id,
            'body_json' => [['question' => 'Why?', 'answer' => 'Because.']],
        ]);

        $this->assertTrue($translation->contentItem->is($item));
        $this->assertSame('Why?', $translation->body_json[0]['question']);
    }

    public function test_publication_and_seo_helpers_use_expected_fallbacks(): void
    {
        $translation = ContentTranslation::factory()->make([
            'status' => 'published',
            'title' => 'Best Monitors',
            'excerpt' => 'A useful guide.',
            'meta_title' => null,
            'meta_description' => null,
        ]);

        $this->assertTrue($translation->isPublished());
        $this->assertSame('Best Monitors', $translation->seoTitle());
        $this->assertSame('A useful guide.', $translation->seoDescription());
    }
}

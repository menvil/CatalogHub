<?php

namespace Tests\Feature\Actions;

use App\Actions\Content\UpsertContentTranslationAction;
use App\Models\ContentItem;
use App\Models\ContentTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class UpsertContentTranslationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_and_updates_one_translation_per_item_and_locale(): void
    {
        $item = ContentItem::factory()->create();
        $action = app(UpsertContentTranslationAction::class);

        $created = $action->handle($item, [
            'locale' => 'en-US',
            'title' => 'Original title',
            'slug' => 'original-title',
            'status' => 'draft',
        ]);
        $updated = $action->handle($item, [
            'locale' => 'en-US',
            'title' => 'Updated title',
            'slug' => 'updated-title',
            'status' => 'published',
        ]);

        $this->assertTrue($created->is($updated));
        $this->assertSame('Updated title', $updated->title);
        $this->assertSame('published', $updated->status);
        $this->assertSame(1, ContentTranslation::query()->count());
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Enums\ContentType;
use App\Filament\Resources\ContentItemResource\Pages\CreateContentItem;
use App\Filament\Resources\ContentItemResource\Pages\EditContentItem;
use App\Models\ContentItem;
use App\Models\ContentTranslation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ArticleEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_admin_can_create_article_with_default_translation(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en-US']);
        $admin = User::factory()->siteAdmin($site)->create();

        Livewire::actingAs($admin)
            ->test(CreateContentItem::class)
            ->fillForm([
                'site_id' => $site->id,
                'type' => ContentType::Article->value,
                'status' => 'draft',
                'translation_locale' => 'en-US',
                'translation_title' => 'Best monitors',
                'translation_slug' => 'best-monitors',
                'translation_excerpt' => 'A practical monitor guide.',
                'translation_body' => 'Article body.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $item = ContentItem::query()->sole();

        $this->assertSame($site->id, $item->site_id);
        $this->assertSame(ContentType::Article, $item->type);
        $this->assertDatabaseHas('content_translations', [
            'content_item_id' => $item->id,
            'locale' => 'en-US',
            'slug' => 'best-monitors',
            'title' => 'Best monitors',
            'body' => 'Article body.',
        ]);
    }

    public function test_article_requires_title_slug_and_body(): void
    {
        $site = Site::factory()->create();

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(CreateContentItem::class)
            ->fillForm([
                'type' => ContentType::Article->value,
                'translation_title' => null,
                'translation_slug' => null,
                'translation_body' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'translation_title' => 'required',
                'translation_slug' => 'required',
                'translation_body' => 'required',
            ]);

        $this->assertDatabaseCount('content_items', 0);
    }

    public function test_article_slug_is_unique_within_site_and_locale(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en-US']);
        $existing = ContentItem::factory()->for($site)->create();
        ContentTranslation::factory()->for($existing)->create([
            'locale' => 'en-US',
            'slug' => 'best-monitors',
        ]);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(CreateContentItem::class)
            ->fillForm([
                'type' => ContentType::Article->value,
                'status' => 'draft',
                'translation_locale' => 'en-US',
                'translation_title' => 'Another article',
                'translation_slug' => 'best-monitors',
                'translation_body' => 'Another body.',
            ])
            ->call('create')
            ->assertHasFormErrors(['translation_slug']);

        $this->assertDatabaseCount('content_items', 1);
    }

    public function test_site_admin_can_edit_article_translation(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en-US']);
        $item = ContentItem::factory()->for($site)->create(['type' => ContentType::Article]);
        ContentTranslation::factory()->for($item)->create([
            'locale' => 'en-US',
            'title' => 'Old title',
            'slug' => 'old-title',
            'body' => 'Old body.',
        ]);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(EditContentItem::class, ['record' => $item->getRouteKey()])
            ->fillForm([
                'translation_title' => 'New title',
                'translation_slug' => 'new-title',
                'translation_body' => 'New body.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('content_translations', [
            'content_item_id' => $item->id,
            'locale' => 'en-US',
            'title' => 'New title',
            'slug' => 'new-title',
            'body' => 'New body.',
        ]);
    }
}

<?php

namespace Tests\Feature\Admin;

use App\Enums\ContentType;
use App\Filament\Resources\ContentItemResource;
use App\Filament\Resources\ContentItemResource\Pages\EditContentItem;
use App\Filament\Resources\ContentItemResource\RelationManagers\TranslationsRelationManager;
use App\Models\ContentItem;
use App\Models\Locale;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class ContentTranslationEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_resource_exposes_translation_editor(): void
    {
        $this->assertContains(TranslationsRelationManager::class, ContentItemResource::getRelations());
    }

    public function test_site_admin_can_add_enabled_locale_translation(): void
    {
        $site = Site::factory()->create();
        $item = ContentItem::factory()->for($site)->create(['type' => ContentType::Article]);
        $this->enableLocale($site, 'de-DE');

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(TranslationsRelationManager::class, [
                'ownerRecord' => $item,
                'pageClass' => EditContentItem::class,
            ])
            ->callTableAction('create', data: [
                'locale' => 'de-DE',
                'slug' => 'beste-monitore',
                'title' => 'Beste Monitore',
                'excerpt' => 'Ein praktischer Leitfaden.',
                'body' => 'Inhalt.',
                'status' => 'published',
                'meta_title' => 'Beste Monitore 2026',
                'meta_description' => 'Monitore im Vergleich.',
            ])
            ->assertHasNoTableActionErrors();

        $this->assertDatabaseHas('content_translations', [
            'content_item_id' => $item->id,
            'locale' => 'de-DE',
            'slug' => 'beste-monitore',
            'status' => 'published',
            'meta_title' => 'Beste Monitore 2026',
        ]);
    }

    public function test_translation_locale_must_be_enabled_for_site(): void
    {
        $site = Site::factory()->create();
        $item = ContentItem::factory()->for($site)->create();
        Locale::factory()->create(['code' => 'de-DE']);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(TranslationsRelationManager::class, [
                'ownerRecord' => $item,
                'pageClass' => EditContentItem::class,
            ])
            ->callTableAction('create', data: [
                'locale' => 'de-DE',
                'slug' => 'nicht-erlaubt',
                'title' => 'Nicht erlaubt',
                'body' => 'Inhalt.',
                'status' => 'draft',
            ])
            ->assertHasTableActionErrors(['locale']);

        $this->assertDatabaseCount('content_translations', 0);
    }

    private function enableLocale(Site $site, string $code): void
    {
        Locale::factory()->create(['code' => $code]);
        DB::table('site_locales')->insert([
            'site_id' => $site->id,
            'locale_code' => $code,
            'is_enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}

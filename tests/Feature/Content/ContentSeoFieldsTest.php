<?php

namespace Tests\Feature\Content;

use App\Enums\ContentType;
use App\Filament\Resources\ContentItemResource\Pages\CreateContentItem;
use App\Models\ContentTranslation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ContentSeoFieldsTest extends TestCase
{
    use RefreshDatabase;

    public function test_seo_helpers_use_localized_fallback_chain(): void
    {
        $translation = ContentTranslation::factory()->make([
            'title' => 'Best Monitors',
            'excerpt' => 'A practical guide.',
            'meta_title' => null,
            'meta_description' => null,
            'og_title' => null,
            'og_description' => null,
        ]);

        $this->assertSame('Best Monitors', $translation->seoTitle());
        $this->assertSame('A practical guide.', $translation->seoDescription());
        $this->assertSame('Best Monitors', $translation->openGraphTitle());
        $this->assertSame('A practical guide.', $translation->openGraphDescription());

        $translation->meta_title = 'Best Monitors 2026';
        $translation->meta_description = 'Compare current monitor picks.';
        $translation->og_title = 'Monitor guide';
        $translation->og_description = 'Our monitor picks.';

        $this->assertSame('Best Monitors 2026', $translation->seoTitle());
        $this->assertSame('Compare current monitor picks.', $translation->seoDescription());
        $this->assertSame('Monitor guide', $translation->openGraphTitle());
        $this->assertSame('Our monitor picks.', $translation->openGraphDescription());
    }

    public function test_default_translation_editor_saves_seo_fields(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en-US']);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(CreateContentItem::class)
            ->fillForm([
                'type' => ContentType::Article->value,
                'status' => 'draft',
                'translation_locale' => 'en-US',
                'translation_title' => 'Best monitors',
                'translation_slug' => 'best-monitors',
                'translation_excerpt' => 'A guide.',
                'translation_body' => 'Article body.',
                'translation_meta_title' => 'Best Monitors 2026',
                'translation_meta_description' => 'Compare the best monitors.',
                'translation_og_title' => 'Best monitor guide',
                'translation_og_description' => 'Our current monitor picks.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('content_translations', [
            'locale' => 'en-US',
            'meta_title' => 'Best Monitors 2026',
            'meta_description' => 'Compare the best monitors.',
            'og_title' => 'Best monitor guide',
            'og_description' => 'Our current monitor picks.',
        ]);
    }
}

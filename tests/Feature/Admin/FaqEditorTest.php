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

class FaqEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_admin_can_create_structured_faq(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en-US']);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(CreateContentItem::class)
            ->fillForm([
                'type' => ContentType::Faq->value,
                'status' => 'draft',
                'translation_locale' => 'en-US',
                'translation_title' => 'Monitor care FAQ',
                'translation_slug' => 'monitor-care-faq',
                'translation_body' => null,
                'translation_body_json' => [
                    ['question' => 'How should I clean it?', 'answer' => 'Use a microfiber cloth.'],
                    ['question' => 'Can I use alcohol?', 'answer' => 'Follow the manufacturer guidance.'],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $translation = ContentTranslation::query()->sole();

        $this->assertNull($translation->body);
        $this->assertSame('How should I clean it?', $translation->body_json[0]['question']);
        $this->assertSame(1, $translation->body_json[1]['position']);
    }

    public function test_faq_requires_at_least_one_complete_question_and_answer(): void
    {
        $site = Site::factory()->create();

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(CreateContentItem::class)
            ->fillForm([
                'type' => ContentType::Faq->value,
                'translation_locale' => $site->default_locale,
                'translation_title' => 'FAQ',
                'translation_slug' => 'faq',
                'translation_body' => null,
                'translation_body_json' => [
                    ['question' => '', 'answer' => ''],
                ],
            ])
            ->call('create')
            ->assertHasFormErrors([
                'translation_body_json.0.question' => 'required',
                'translation_body_json.0.answer' => 'required',
            ]);

        $this->assertDatabaseCount('content_items', 0);
    }

    public function test_site_admin_can_edit_faq_items(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en-US']);
        $item = ContentItem::factory()->for($site)->create(['type' => ContentType::Faq]);
        ContentTranslation::factory()->for($item)->create([
            'locale' => 'en-US',
            'body' => null,
            'body_json' => [['question' => 'Old?', 'answer' => 'Old.', 'position' => 0]],
        ]);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(EditContentItem::class, ['record' => $item->getRouteKey()])
            ->fillForm([
                'translation_body_json' => [
                    ['question' => 'New?', 'answer' => 'New answer.'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(
            'New answer.',
            ContentTranslation::query()->sole()->body_json[0]['answer'],
        );
    }
}

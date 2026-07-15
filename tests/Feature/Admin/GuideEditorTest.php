<?php

namespace Tests\Feature\Admin;

use App\Enums\ContentType;
use App\Filament\Resources\ContentItemResource\Pages\CreateContentItem;
use App\Models\ContentItem;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuideEditorTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_type_classifies_guide_editors(): void
    {
        $this->assertTrue(ContentType::BuyingGuide->isGuide());
        $this->assertTrue(ContentType::HowToGuide->isGuide());
        $this->assertTrue(ContentType::TroubleshootingGuide->isGuide());
        $this->assertTrue(ContentType::Manual->isGuide());
        $this->assertFalse(ContentType::Article->isGuide());
        $this->assertFalse(ContentType::Faq->isGuide());
    }

    public function test_site_admin_can_create_each_guide_type_with_shared_fields(): void
    {
        $site = Site::factory()->create(['default_locale' => 'en-US']);
        $admin = User::factory()->siteAdmin($site)->create();
        $types = [
            ContentType::BuyingGuide,
            ContentType::HowToGuide,
            ContentType::TroubleshootingGuide,
            ContentType::Manual,
        ];

        foreach ($types as $index => $type) {
            Livewire::actingAs($admin)
                ->test(CreateContentItem::class)
                ->fillForm([
                    'type' => $type->value,
                    'status' => 'draft',
                    'translation_locale' => 'en-US',
                    'translation_title' => $type->label(),
                    'translation_slug' => $type->value.'-'.$index,
                    'translation_body' => 'Shared guide body.',
                ])
                ->call('create')
                ->assertHasNoFormErrors();
        }

        $this->assertSame(
            array_map(fn (ContentType $type): string => $type->value, $types),
            ContentItem::query()->orderBy('id')->pluck('type')->map(
                fn (ContentType $type): string => $type->value,
            )->all(),
        );
        $this->assertDatabaseCount('content_translations', 4);
    }
}

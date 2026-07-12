<?php

namespace Tests\Feature\Actions;

use App\Actions\Translations\MarkTranslationOutdatedAction;
use App\Enums\TranslationStatus;
use App\Models\Translations\ProductTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkTranslationOutdatedActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_translation_as_outdated(): void
    {
        $translation = ProductTranslation::factory()->create([
            'status' => TranslationStatus::Approved,
        ]);

        app(MarkTranslationOutdatedAction::class)->handle(
            translation: $translation,
            reason: 'Source product name changed.',
        );

        $this->assertSame(TranslationStatus::Outdated, $translation->fresh()->status);
    }
}

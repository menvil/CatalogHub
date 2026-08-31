<?php

namespace Tests\Feature\Actions;

use App\Actions\Translations\MarkTranslationOutdatedAction;
use App\Enums\TranslationStatus;
use App\Models\Translations\ProductTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarkTranslationOutdatedActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_translation_as_outdated(): void
    {
        $approver = User::factory()->create();
        $translation = ProductTranslation::factory()->create([
            'status' => TranslationStatus::Approved,
            'approved_at' => now(),
            'approved_by_user_id' => $approver->id,
        ]);

        app(MarkTranslationOutdatedAction::class)->handle($translation);

        $outdated = $translation->fresh();
        $this->assertSame(TranslationStatus::Outdated, $outdated->status);
        $this->assertNull($outdated->approved_at);
        $this->assertNull($outdated->approved_by_user_id);

        $updatedAt = $outdated->updated_at;
        app(MarkTranslationOutdatedAction::class)->handle($outdated);
        $this->assertTrue($updatedAt?->equalTo($outdated->fresh()->updated_at));
    }
}

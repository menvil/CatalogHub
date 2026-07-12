<?php

namespace Tests\Feature\Actions;

use App\Actions\Translations\ApproveTranslationAction;
use App\Enums\TranslationStatus;
use App\Models\Translations\ProductTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApproveTranslationActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_approves_product_translation(): void
    {
        $admin = User::factory()->centralAdmin()->create();
        $translation = ProductTranslation::factory()->create([
            'status' => TranslationStatus::HumanReviewed,
            'approved_at' => null,
            'approved_by_user_id' => null,
        ]);

        app(ApproveTranslationAction::class)->handle($translation, $admin);

        $translation->refresh();

        $this->assertSame(TranslationStatus::Approved, $translation->status);
        $this->assertNotNull($translation->approved_at);
        $this->assertSame($admin->id, $translation->approved_by_user_id);
    }
}

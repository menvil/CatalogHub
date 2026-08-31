<?php

namespace Tests\Feature\Actions;

use App\Actions\Translations\ApproveTranslationAction;
use App\Enums\TranslationStatus;
use App\Models\Translations\ProductTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
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

    public function test_only_human_reviewed_translation_is_eligible_and_approved_is_a_no_op(): void
    {
        $actor = User::factory()->create();
        $action = app(ApproveTranslationAction::class);

        foreach ([TranslationStatus::Missing, TranslationStatus::MachineTranslated, TranslationStatus::Outdated] as $status) {
            $translation = ProductTranslation::factory()->create(['status' => $status]);

            try {
                $action->handle($translation, $actor);
                $this->fail('Expected approval validation failure.');
            } catch (ValidationException) {
                $this->assertSame($status, $translation->fresh()->status);
            }
        }

        $approvedAt = now()->subDay();
        $approved = ProductTranslation::factory()->create([
            'status' => TranslationStatus::Approved,
            'approved_at' => $approvedAt,
            'approved_by_user_id' => $actor->id,
        ]);
        $result = $action->handle($approved, User::factory()->create());

        $this->assertTrue($approved->is($result));
        $this->assertSame($actor->id, $result->getAttribute('approved_by_user_id'));
        $this->assertSame($approvedAt->format('Y-m-d H:i:s'), $result->getRawOriginal('approved_at'));
    }
}

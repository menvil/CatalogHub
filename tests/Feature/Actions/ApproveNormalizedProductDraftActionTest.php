<?php

namespace Tests\Feature\Actions;

use App\Actions\Imports\ApproveNormalizedProductDraftAction;
use App\Enums\UserRole;
use App\Filament\Resources\NormalizedProductDraftResource\Pages\ViewNormalizedProductDraft;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\NormalizationError;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class ApproveNormalizedProductDraftActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_editor_can_approve_reviewable_draft_without_publishing(): void
    {
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $draft = NormalizedProductDraft::factory()->create(['status' => 'pending_review']);

        $approved = app(ApproveNormalizedProductDraftAction::class)->handle($draft, $editor);

        $this->assertSame('approved', $approved->status);
        $this->assertSame($editor->id, $approved->approved_by_user_id);
        $this->assertNotNull($approved->approved_at);
        $this->assertSame(1, $approved->importBatch->approved_count);
        $this->assertSame(0, CentralProduct::query()->count());
    }

    public function test_unresolved_critical_error_blocks_approval(): void
    {
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $draft = NormalizedProductDraft::factory()->create(['status' => 'pending_review']);
        NormalizationError::query()->create([
            'import_batch_id' => $draft->import_batch_id,
            'raw_product_id' => $draft->raw_product_id,
            'normalized_product_draft_id' => $draft->id,
            'severity' => 'critical',
            'code' => 'missing_category',
            'message' => 'Category is required',
        ]);

        $this->expectException(LogicException::class);

        app(ApproveNormalizedProductDraftAction::class)->handle($draft, $editor);
    }

    public function test_action_and_ui_enforce_role_and_review_status(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $draft = NormalizedProductDraft::factory()->create(['status' => 'pending_review']);

        try {
            app(ApproveNormalizedProductDraftAction::class)->handle($draft, $moderator);
            $this->fail('Moderator approval was not blocked.');
        } catch (AuthorizationException) {
            $this->assertSame('pending_review', $draft->fresh()->status);
        }

        Livewire::actingAs($editor)
            ->test(ViewNormalizedProductDraft::class, ['record' => $draft->id])
            ->callAction('approve');

        $this->assertSame('approved', $draft->fresh()->status);

        $this->expectException(LogicException::class);
        app(ApproveNormalizedProductDraftAction::class)->handle($draft->fresh(), $editor);
    }
}

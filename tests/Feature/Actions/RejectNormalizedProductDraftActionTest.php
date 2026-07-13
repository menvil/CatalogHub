<?php

namespace Tests\Feature\Actions;

use App\Actions\Imports\ApproveNormalizedProductDraftAction;
use App\Actions\Imports\RejectNormalizedProductDraftAction;
use App\Enums\UserRole;
use App\Filament\Resources\NormalizedProductDraftResource\Pages\ViewNormalizedProductDraft;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Livewire\Livewire;
use LogicException;
use Tests\TestCase;

class RejectNormalizedProductDraftActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_editor_can_reject_draft_with_required_reason(): void
    {
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $draft = NormalizedProductDraft::factory()->create(['status' => 'pending_review']);

        $rejected = app(RejectNormalizedProductDraftAction::class)->handle(
            $draft,
            $editor,
            'Source product is not relevant',
        );

        $this->assertSame('rejected', $rejected->status);
        $this->assertSame('Source product is not relevant', $rejected->review_notes);
        $this->assertNull($rejected->approved_by_user_id);
        $this->assertNull($rejected->approved_at);
        $this->assertSame(1, $rejected->importBatch->rejected_count);
        $this->assertSame(0, CentralProduct::query()->count());
    }

    public function test_blank_reason_is_rejected_without_state_change(): void
    {
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $draft = NormalizedProductDraft::factory()->create(['status' => 'pending_review']);

        try {
            app(RejectNormalizedProductDraftAction::class)->handle($draft, $editor, '   ');
            $this->fail('Blank rejection reason was accepted.');
        } catch (InvalidArgumentException) {
            $this->assertSame('pending_review', $draft->fresh()->status);
        }
    }

    public function test_ui_rejects_and_rejected_draft_cannot_be_approved(): void
    {
        $editor = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $draft = NormalizedProductDraft::factory()->create(['status' => 'pending_review']);

        Livewire::actingAs($editor)
            ->test(ViewNormalizedProductDraft::class, ['record' => $draft->id])
            ->callAction('reject', data: ['reason' => 'Duplicate source row']);

        $draft = $draft->fresh();
        $this->assertSame('rejected', $draft->status);

        $this->expectException(LogicException::class);
        app(ApproveNormalizedProductDraftAction::class)->handle($draft, $editor);
    }
}

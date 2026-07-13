<?php

namespace App\Actions\Imports;

use App\Models\Imports\NormalizedProductDraft;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class RejectNormalizedProductDraftAction
{
    public function handle(
        NormalizedProductDraft $draft,
        ?User $user,
        string $reason,
    ): NormalizedProductDraft {
        if (! $user instanceof User || ! ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->isCatalogEditor())) {
            throw new AuthorizationException('You are not allowed to reject normalized drafts.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        return DB::transaction(function () use ($draft, $reason): NormalizedProductDraft {
            $lockedDraft = NormalizedProductDraft::query()->lockForUpdate()->findOrFail($draft->id);

            if ($lockedDraft->status !== 'pending_review') {
                throw new LogicException("Draft [{$lockedDraft->id}] is not rejectable from status [{$lockedDraft->status}].");
            }

            $lockedDraft->forceFill([
                'status' => 'rejected',
                'review_notes' => $reason,
                'approved_by_user_id' => null,
                'approved_at' => null,
            ])->save();
            $lockedDraft->importBatch()->increment('rejected_count');

            return $lockedDraft->refresh();
        });
    }
}

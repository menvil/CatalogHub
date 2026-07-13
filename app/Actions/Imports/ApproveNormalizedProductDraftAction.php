<?php

namespace App\Actions\Imports;

use App\Models\Imports\NormalizedProductDraft;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use LogicException;

final class ApproveNormalizedProductDraftAction
{
    public function handle(NormalizedProductDraft $draft, ?User $user): NormalizedProductDraft
    {
        if (! $user instanceof User || ! ($user->isSuperAdmin() || $user->isCentralAdmin() || $user->isCatalogEditor())) {
            throw new AuthorizationException('You are not allowed to approve normalized drafts.');
        }

        return DB::transaction(function () use ($draft, $user): NormalizedProductDraft {
            $lockedDraft = NormalizedProductDraft::query()->lockForUpdate()->findOrFail($draft->id);

            if ($lockedDraft->status !== 'pending_review') {
                throw new LogicException("Draft [{$lockedDraft->id}] is not reviewable from status [{$lockedDraft->status}].");
            }

            if ($lockedDraft->errors()->where('severity', 'critical')->whereNull('resolved_at')->exists()) {
                throw new LogicException("Draft [{$lockedDraft->id}] has unresolved critical normalization errors.");
            }

            $lockedDraft->forceFill([
                'status' => 'approved',
                'approved_by_user_id' => $user->id,
                'approved_at' => now(),
            ])->save();
            $lockedDraft->importBatch()->increment('approved_count');

            return $lockedDraft->refresh();
        });
    }
}

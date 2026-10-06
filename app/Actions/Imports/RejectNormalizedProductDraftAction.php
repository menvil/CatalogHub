<?php

namespace App\Actions\Imports;

use App\Enums\Permission;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\User;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use App\Services\Categories\CategoryAccess;
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
        app(CategoryAccess::class)->authorize(Permission::CatalogProductsManage, $user);

        $reason = trim($reason);

        if ($reason === '') {
            throw new InvalidArgumentException('A rejection reason is required.');
        }

        return DB::transaction(function () use ($draft, $reason): NormalizedProductDraft {
            app(AttributeIdentityLock::class)->acquireTarget();
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

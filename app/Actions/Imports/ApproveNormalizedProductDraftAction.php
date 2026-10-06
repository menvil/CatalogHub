<?php

namespace App\Actions\Imports;

use App\Enums\Permission;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\User;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use App\Services\AttributeGlobalization\DraftAttributeIdentityV2;
use App\Services\Categories\CategoryAccess;
use Illuminate\Support\Facades\DB;
use LogicException;

final class ApproveNormalizedProductDraftAction
{
    public function handle(NormalizedProductDraft $draft, ?User $user): NormalizedProductDraft
    {
        $user = app(CategoryAccess::class)->authorize(Permission::CatalogProductsManage, $user);

        return DB::transaction(function () use ($draft, $user): NormalizedProductDraft {
            app(AttributeIdentityLock::class)->acquireTarget();
            $lockedDraft = NormalizedProductDraft::query()->lockForUpdate()->findOrFail($draft->id);

            if ($lockedDraft->status !== 'pending_review') {
                throw new LogicException("Draft [{$lockedDraft->id}] is not reviewable from status [{$lockedDraft->status}].");
            }

            if ($lockedDraft->errors()->where('severity', 'critical')->whereNull('resolved_at')->exists()) {
                throw new LogicException("Draft [{$lockedDraft->id}] has unresolved critical normalization errors.");
            }

            if ($lockedDraft->attribute_identity_version !== 2) {
                throw new LogicException('Migrate active draft identity before approval.');
            }
            app(DraftAttributeIdentityV2::class)->candidates($lockedDraft);
            $lockedDraft->forceFill([
                'status' => 'approved',
                'approved_by_user_id' => $user->id,
                'approved_at' => now(),
            ])->save();
            $lockedDraft->importBatch()->increment('approved_count');

            app(AttributeIdentityLock::class)->recordTargetWrite();

            return $lockedDraft->refresh();
        });
    }
}

<?php

namespace App\Actions\Imports;

use App\Enums\Permission;
use App\Models\Imports\NormalizedProductDraft;
use App\Models\User;
use App\Services\AttributeGlobalization\AttributeIdentityLock;
use App\Services\Categories\CategoryAccess;
use App\Services\Imports\DraftAttributeIdentity;
use Illuminate\Support\Facades\DB;
use LogicException;

final class ApproveNormalizedProductDraftAction
{
    public function handle(NormalizedProductDraft $draft, ?User $user): NormalizedProductDraft
    {
        $user = app(CategoryAccess::class)->authorize(Permission::CatalogProductsManage, $user);

        return DB::transaction(function () use ($draft, $user): NormalizedProductDraft {
            app(AttributeIdentityLock::class)->acquire();
            $lockedDraft = NormalizedProductDraft::query()->lockForUpdate()->findOrFail($draft->id);

            if ($lockedDraft->status !== 'pending_review') {
                throw new LogicException("Draft [{$lockedDraft->id}] is not reviewable from status [{$lockedDraft->status}].");
            }

            if ($lockedDraft->errors()->where('severity', 'critical')->whereNull('resolved_at')->exists()) {
                throw new LogicException("Draft [{$lockedDraft->id}] has unresolved critical normalization errors.");
            }

            if ($lockedDraft->schema_version !== 1) {
                throw new LogicException('Unsupported draft schema version.');
            }
            app(DraftAttributeIdentity::class)->candidates($lockedDraft);
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

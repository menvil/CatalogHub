<?php

namespace App\Actions\Sync;

use App\Enums\SyncConflictStatus;
use App\Exceptions\Sync\CannotResolveSyncConflictException;
use App\Models\SiteOverride;
use App\Models\SyncConflict;
use App\Models\User;
use App\Services\Sync\SyncLogWriter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class UseCentralValueAction
{
    public function __construct(private readonly SyncLogWriter $syncLogWriter) {}

    public function handle(User $admin, SyncConflict $conflict): SyncConflict
    {
        if (! $admin->hasCatalogHubPermission('central.manage')) {
            throw new AuthorizationException('Only a central administrator can resolve sync conflicts.');
        }

        return DB::transaction(function () use ($admin, $conflict): SyncConflict {
            $lockedConflict = SyncConflict::query()
                ->whereKey($conflict->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedConflict->status !== SyncConflictStatus::Open) {
                throw CannotResolveSyncConflictException::because('Only an open sync conflict can be resolved.');
            }

            $entityType = $lockedConflict->entity_type === 'central_product'
                ? 'product'
                : $lockedConflict->entity_type;
            $entityId = $lockedConflict->central_product_id ?? $lockedConflict->entity_id;

            if ($entityId !== null) {
                SiteOverride::query()
                    ->where('site_id', $lockedConflict->site_id)
                    ->where('entity_type', $entityType)
                    ->where('entity_id', $entityId)
                    ->where('field', $lockedConflict->field_path)
                    ->delete();
            }

            $lockedConflict->forceFill([
                'status' => SyncConflictStatus::Resolved,
                'resolution' => 'use_central_value',
                'resolved_by_user_id' => $admin->getKey(),
                'resolved_at' => now(),
            ])->save();

            $this->syncLogWriter->completed(
                operation: 'resolve_sync_conflict',
                triggeredBy: 'user',
                actor: $admin,
                site: $lockedConflict->site,
                product: $lockedConflict->centralProduct,
                affectedCount: 1,
                context: [
                    'sync_conflict_id' => $lockedConflict->getKey(),
                    'resolution' => 'use_central_value',
                ],
            );

            return $lockedConflict;
        });
    }
}

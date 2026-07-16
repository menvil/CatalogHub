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

final class KeepLocalOverrideAction
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

            $entityId = $lockedConflict->central_product_id ?? $lockedConflict->entity_id;

            if ($entityId === null) {
                throw CannotResolveSyncConflictException::because('The conflict does not identify a local override target.');
            }

            $entityType = $lockedConflict->entity_type === 'central_product'
                ? 'product'
                : $lockedConflict->entity_type;
            $localeCode = (string) data_get($lockedConflict->metadata_json, 'locale_code', '');
            $localValue = $lockedConflict->local_value_json;

            SiteOverride::query()->updateOrCreate([
                'site_id' => $lockedConflict->site_id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'field' => $lockedConflict->field_path,
                'locale_code' => $localeCode,
            ], [
                'value_json' => is_array($localValue) && array_key_exists('value', $localValue)
                    ? $localValue
                    : ['value' => $localValue],
                'reason' => "Kept while resolving sync conflict #{$lockedConflict->getKey()}.",
                'status' => 'active',
            ]);

            $lockedConflict->forceFill([
                'status' => SyncConflictStatus::Resolved,
                'resolution' => 'keep_local_override',
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
                    'resolution' => 'keep_local_override',
                ],
            );

            return $lockedConflict;
        });
    }
}

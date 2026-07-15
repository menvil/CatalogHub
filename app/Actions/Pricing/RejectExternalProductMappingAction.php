<?php

namespace App\Actions\Pricing;

use App\Enums\ExternalProductMappingStatus;
use App\Exceptions\Pricing\CannotRejectExternalProductMappingException;
use App\Models\ExternalProductMapping;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class RejectExternalProductMappingAction
{
    /** @throws AuthorizationException */
    public function handle(User $admin, ExternalProductMapping $mapping, string $reason): ExternalProductMapping
    {
        if (! $admin->hasCatalogHubPermission('prices.manage')) {
            throw new AuthorizationException('You are not allowed to reject external product mappings.');
        }

        $reason = trim($reason);

        if ($reason === '') {
            throw new CannotRejectExternalProductMappingException('A rejection reason is required.');
        }

        DB::transaction(function () use ($admin, $mapping, $reason): void {
            $locked = ExternalProductMapping::query()->lockForUpdate()->findOrFail($mapping->id);

            if ($locked->status !== ExternalProductMappingStatus::Pending) {
                throw new CannotRejectExternalProductMappingException('Only pending mappings can be rejected.');
            }

            $existingNotes = trim((string) $locked->notes);
            $notes = $existingNotes === ''
                ? $reason
                : "{$existingNotes}\n\nRejected: {$reason}";

            $locked->update([
                'status' => ExternalProductMappingStatus::Rejected,
                'approved_at' => null,
                'approved_by_user_id' => null,
                'rejected_at' => now(),
                'rejected_by_user_id' => $admin->id,
                'notes' => $notes,
                'metadata' => [
                    ...($locked->metadata ?? []),
                    'last_mapping_action' => [
                        'action' => 'rejected',
                        'user_id' => $admin->id,
                        'reason' => $reason,
                        'at' => now()->toISOString(),
                    ],
                ],
            ]);
        });

        return $mapping->refresh();
    }
}

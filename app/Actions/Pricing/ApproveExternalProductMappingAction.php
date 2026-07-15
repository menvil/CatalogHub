<?php

namespace App\Actions\Pricing;

use App\Enums\ExternalProductMappingStatus;
use App\Exceptions\Pricing\CannotApproveExternalProductMappingException;
use App\Models\ExternalProductMapping;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class ApproveExternalProductMappingAction
{
    /** @throws AuthorizationException */
    public function handle(User $admin, ExternalProductMapping $mapping): ExternalProductMapping
    {
        if (! $admin->hasCatalogHubPermission('prices.manage')) {
            throw new AuthorizationException('You are not allowed to approve external product mappings.');
        }

        DB::transaction(function () use ($admin, $mapping): void {
            $locked = ExternalProductMapping::query()->lockForUpdate()->findOrFail($mapping->id);

            if ($locked->status !== ExternalProductMappingStatus::Pending) {
                throw new CannotApproveExternalProductMappingException('Only pending mappings can be approved.');
            }

            if ($locked->central_product_id === null) {
                throw new CannotApproveExternalProductMappingException(
                    'A central product must be selected before approving the mapping.',
                );
            }

            $locked->update([
                'status' => ExternalProductMappingStatus::Approved,
                'approved_at' => now(),
                'approved_by_user_id' => $admin->id,
                'rejected_at' => null,
                'rejected_by_user_id' => null,
                'metadata' => [
                    ...($locked->metadata ?? []),
                    'last_mapping_action' => [
                        'action' => 'approved',
                        'user_id' => $admin->id,
                        'at' => now()->toISOString(),
                    ],
                ],
            ]);
        });

        return $mapping->refresh();
    }
}

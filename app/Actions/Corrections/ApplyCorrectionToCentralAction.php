<?php

namespace App\Actions\Corrections;

use App\Actions\Versions\IncrementProductVersionAction;
use App\Enums\ChangeRequestStatus;
use App\Exceptions\Corrections\CannotApplyCorrectionException;
use App\Jobs\Projections\RebuildProductProjectionJob;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ChangeRequest;
use App\Models\SiteProduct;
use App\Models\User;
use App\Services\Corrections\CanonicalCorrectionFieldResolver;
use App\Services\Sync\SyncLogWriter;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final class ApplyCorrectionToCentralAction
{
    public function __construct(
        private readonly CanonicalCorrectionFieldResolver $fieldResolver,
        private readonly IncrementProductVersionAction $incrementProductVersion,
        private readonly SyncLogWriter $syncLogWriter,
    ) {}

    public function handle(User $admin, ChangeRequest $request): ChangeRequest
    {
        if (! $admin->hasCatalogHubPermission('corrections.review')) {
            throw new AuthorizationException('Only a central administrator can apply corrections.');
        }

        return DB::transaction(function () use ($admin, $request): ChangeRequest {
            $lockedRequest = ChangeRequest::query()
                ->whereKey($request->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== ChangeRequestStatus::Approved) {
                throw CannotApplyCorrectionException::because('Only an approved correction can be applied.');
            }

            if ($lockedRequest->entity_type !== 'central_product' || $lockedRequest->central_product_id === null) {
                throw CannotApplyCorrectionException::because('The correction does not target a central product.');
            }

            if (! $this->fieldResolver->supports($lockedRequest->field_path)) {
                throw CannotApplyCorrectionException::because('The requested canonical field is not supported.');
            }

            $product = CentralProduct::query()
                ->whereKey($lockedRequest->central_product_id)
                ->lockForUpdate()
                ->firstOrFail();
            $oldValue = $this->fieldResolver->currentValue($product, $lockedRequest->field_path);
            $proposedValue = $this->proposedValue($lockedRequest);

            $this->fieldResolver->apply($product, $lockedRequest->field_path, $proposedValue);

            $version = $this->incrementProductVersion->handle(
                product: $product,
                changedBy: $admin,
                changeType: 'correction',
                reason: "Applied correction request #{$lockedRequest->getKey()}.",
                diff: [
                    $lockedRequest->field_path => [
                        'old' => $oldValue,
                        'new' => $proposedValue,
                    ],
                ],
                metadata: [
                    'change_request_id' => $lockedRequest->getKey(),
                    'source_site_id' => $lockedRequest->site_id,
                ],
            );

            $lockedRequest->forceFill([
                'status' => ChangeRequestStatus::Applied,
                'applied_by_user_id' => $admin->getKey(),
                'applied_at' => now(),
            ])->save();

            $affectedSiteProductIds = SiteProduct::query()
                ->where('central_product_id', $product->getKey())
                ->pluck('id');

            SiteProduct::query()
                ->whereKey($affectedSiteProductIds)
                ->update(['sync_status' => 'queued']);

            foreach ($affectedSiteProductIds as $siteProductId) {
                RebuildProductProjectionJob::dispatch(
                    (int) $siteProductId,
                    (int) $admin->getKey(),
                )->afterCommit();
            }

            $this->syncLogWriter->completed(
                operation: 'apply_correction',
                triggeredBy: 'correction',
                actor: $admin,
                site: $lockedRequest->site,
                product: $product,
                affectedCount: 1,
                context: [
                    'change_request_id' => $lockedRequest->getKey(),
                    'product_version' => $version->version,
                    'field_path' => $lockedRequest->field_path,
                    'queued_site_product_ids' => $affectedSiteProductIds->all(),
                    'queued_projection_count' => $affectedSiteProductIds->count(),
                ],
            );

            return $lockedRequest;
        });
    }

    private function proposedValue(ChangeRequest $request): mixed
    {
        $value = $request->proposed_value_json;

        return is_array($value) && array_key_exists('value', $value)
            ? $value['value']
            : $value;
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\CentralCatalog;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralBrandOwnership;
use App\Models\Organization;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

final readonly class ClearCentralBrandOwnerAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function handle(User $actor, CentralBrand $brand): CentralBrand
    {
        return DB::transaction(function () use ($actor, $brand): CentralBrand {
            $lockedBrand = CentralBrand::query()->lockForUpdate()->findOrFail($brand->getKey());
            $ownership = CentralBrandOwnership::query()
                ->where('central_brand_id', $lockedBrand->getKey())
                ->lockForUpdate()
                ->first();

            if ($ownership === null) {
                return $lockedBrand->setRelation('ownership', null);
            }

            $organization = Organization::query()->lockForUpdate()->findOrFail($ownership->organization_id);
            $ownership->deleteOrFail();

            $this->audit->record(
                AuditAction::CatalogBrandOwnerCleared,
                AuditContext::Central,
                $actor,
                $lockedBrand,
                null,
                [
                    'organization_id' => (int) $organization->getKey(),
                    'organization_name' => $organization->name,
                ],
                null,
            );

            return $lockedBrand->setRelation('ownership', null);
        });
    }
}

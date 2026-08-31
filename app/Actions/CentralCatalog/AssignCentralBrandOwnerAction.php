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

final readonly class AssignCentralBrandOwnerAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function handle(User $actor, CentralBrand $brand, Organization $organization): CentralBrand
    {
        return DB::transaction(function () use ($actor, $brand, $organization): CentralBrand {
            $lockedBrand = CentralBrand::query()->lockForUpdate()->findOrFail($brand->getKey());
            $lockedOrganization = Organization::query()->lockForUpdate()->findOrFail($organization->getKey());
            $ownership = CentralBrandOwnership::query()
                ->where('central_brand_id', $lockedBrand->getKey())
                ->lockForUpdate()
                ->first();

            if ($ownership?->organization_id === $lockedOrganization->getKey()) {
                return $lockedBrand->setRelation(
                    'ownership',
                    $ownership->setRelation('organization', $lockedOrganization),
                );
            }

            $previousOrganization = $ownership === null
                ? null
                : Organization::query()->lockForUpdate()->findOrFail($ownership->organization_id);

            if ($ownership === null) {
                $ownership = new CentralBrandOwnership;
                $ownership->central_brand_id = $lockedBrand->getKey();
            }

            $ownership->organization_id = $lockedOrganization->getKey();
            $ownership->saveOrFail();

            $this->audit->record(
                AuditAction::CatalogBrandOwnerAssigned,
                AuditContext::Central,
                $actor,
                $lockedBrand,
                null,
                $previousOrganization === null ? null : $this->snapshot($previousOrganization),
                $this->snapshot($lockedOrganization),
            );

            return $lockedBrand->setRelation(
                'ownership',
                $ownership->setRelation('organization', $lockedOrganization),
            );
        });
    }

    /** @return array{organization_id: int, organization_name: string} */
    private function snapshot(Organization $organization): array
    {
        return [
            'organization_id' => (int) $organization->getKey(),
            'organization_name' => $organization->name,
        ];
    }
}

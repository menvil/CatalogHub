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
use Illuminate\Support\Facades\Gate;

final readonly class AssignCentralBrandOwnerAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function handle(User $actor, CentralBrand $brand, Organization $organization): CentralBrand
    {
        Gate::forUser($actor)->authorize('catalog.brands.manage');

        return DB::transaction(function () use ($actor, $brand, $organization): CentralBrand {
            $lockedBrand = CentralBrand::query()->lockForUpdate()->findOrFail($brand->getKey());
            $ownership = CentralBrandOwnership::query()
                ->where('central_brand_id', $lockedBrand->getKey())
                ->lockForUpdate()
                ->first();

            $organizationIds = array_values(array_unique(array_map(
                static fn (mixed $id): int => (int) $id,
                array_filter([
                    $organization->getKey(),
                    $ownership?->organization_id,
                ], static fn (mixed $id): bool => $id !== null),
            )));
            sort($organizationIds);

            /** @var array<int, Organization> $lockedOrganizations */
            $lockedOrganizations = [];
            foreach ($organizationIds as $organizationId) {
                $lockedOrganizations[$organizationId] = Organization::query()
                    ->lockForUpdate()
                    ->findOrFail($organizationId);
            }

            $lockedOrganization = $lockedOrganizations[(int) $organization->getKey()];

            if ($ownership?->organization_id === $lockedOrganization->getKey()) {
                return $lockedBrand->setRelation(
                    'ownership',
                    $ownership->setRelation('organization', $lockedOrganization),
                );
            }

            $previousOrganization = $ownership === null
                ? null
                : $lockedOrganizations[$ownership->organization_id];

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

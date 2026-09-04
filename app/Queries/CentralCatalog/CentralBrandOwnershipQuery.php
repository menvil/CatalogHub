<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\Organization;

final class CentralBrandOwnershipQuery
{
    public function loadForEditor(CentralBrand $brand): CentralBrand
    {
        return $brand->load('ownership.organization');
    }

    public function findOrganization(int $organizationId): Organization
    {
        return Organization::query()->findOrFail($organizationId);
    }
}

<?php

namespace App\Policies;

use App\Models\CatalogSnapshot;
use App\Models\User;

final class CatalogSnapshotPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasCatalogHubPermission('central.manage');
    }

    public function view(User $user, CatalogSnapshot $snapshot): bool
    {
        return $user->hasCatalogHubPermission('central.manage');
    }

    public function download(User $user, CatalogSnapshot $snapshot): bool
    {
        return $user->hasCatalogHubPermission('central.manage');
    }
}

<?php

namespace App\Policies;

use App\Models\MediaAsset;
use App\Models\User;

final class MediaAssetPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasCatalogHubPermission('media.manage');
    }

    public function view(User $user, MediaAsset $asset): bool
    {
        return $user->hasCatalogHubPermission('media.manage');
    }

    public function create(User $user): bool
    {
        return $user->hasCatalogHubPermission('media.manage');
    }

    public function update(User $user, MediaAsset $asset): bool
    {
        return $user->hasCatalogHubPermission('media.manage');
    }
}

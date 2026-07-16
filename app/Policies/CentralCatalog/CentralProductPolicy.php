<?php

namespace App\Policies\CentralCatalog;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\User;

final class CentralProductPolicy
{
    public function manageMedia(User $user, CentralProduct $product): bool
    {
        return $user->hasCatalogHubPermission('media.manage');
    }
}

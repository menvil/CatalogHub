<?php

declare(strict_types=1);

namespace App\Policies;

use App\Contracts\Auth\CentralAdminAccess;
use App\Enums\Permission;
use App\Models\User;

final class CentralPanelPolicy implements CentralAdminAccess
{
    public function allows(User $user): bool
    {
        return $user->hasCatalogHubPermission(Permission::CentralPanelAccess->value);
    }
}

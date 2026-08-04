<?php

declare(strict_types=1);

namespace App\Policies;

use App\Contracts\Auth\CentralAdminAccess;
use App\Enums\Permission;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use App\Services\Auth\SiteOwnedCentralRouteAccess;

final readonly class CentralPanelPolicy implements CentralAdminAccess
{
    public function __construct(
        private AuthorizationService $authorization,
        private SiteOwnedCentralRouteAccess $siteOwnedRoutes,
    ) {}

    public function allows(User $user, ?string $routeName = null): bool
    {
        return $this->authorization->allowsPanel($user, Permission::CentralPanelAccess)
            || $this->siteOwnedRoutes->allows($user, $routeName);
    }
}

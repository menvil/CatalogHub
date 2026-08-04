<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\Permission;
use App\Models\User;

final class SiteOwnedCentralRouteAccess
{
    /** @var array<string, Permission> */
    private const ROUTE_PERMISSIONS = [
        'filament.central.resources.sites.' => Permission::SitesManage,
        'filament.central.resources.content-items.' => Permission::SiteContentManage,
        'filament.central.resources.correction-requests.' => Permission::CorrectionsRequest,
        'filament.central.resources.leads.' => Permission::LeadsManage,
        'filament.central.resources.reviews.' => Permission::ReviewsModerate,
    ];

    public function allows(User $user, ?string $routeName): bool
    {
        if (! $user->isActive() || $routeName === null) {
            return false;
        }

        foreach (self::ROUTE_PERMISSIONS as $prefix => $permission) {
            if (str_starts_with($routeName, $prefix)) {
                return $user->hasCatalogHubPermission($permission->value)
                    && $user->memberships()->where('is_active', true)->exists();
            }
        }

        return false;
    }
}

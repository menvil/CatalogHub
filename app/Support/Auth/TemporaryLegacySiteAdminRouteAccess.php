<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Contracts\Auth\LegacySiteAdminRouteAccess;
use App\Models\User;

/**
 * Keeps pre-Phase-0.2 Site-owned resources usable until P00-029 moves them.
 */
final class TemporaryLegacySiteAdminRouteAccess implements LegacySiteAdminRouteAccess
{
    /** @var list<string> */
    private const ROUTE_PREFIXES = [
        'filament.central.resources.sites.',
        'filament.central.resources.content-items.',
        'filament.central.resources.correction-requests.',
        'filament.central.resources.leads.',
        'filament.central.resources.reviews.',
    ];

    public function allows(User $user, ?string $routeName): bool
    {
        if (! $user->isSiteAdmin() || $routeName === null) {
            return false;
        }

        foreach (self::ROUTE_PREFIXES as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }
}

<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Contracts\Auth\SiteAdminAccess;
use App\Models\User;

/**
 * Phase 0.2 adapter. P00-029 owns replacement with site-scoped permissions.
 */
final class TemporarySiteAdminAccess implements SiteAdminAccess
{
    public function allows(User $user): bool
    {
        return $user->isSiteAdmin();
    }
}

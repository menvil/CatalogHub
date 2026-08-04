<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Contracts\Auth\CentralAdminAccess;
use App\Models\User;

/**
 * Phase 0.2 adapter. P00-027 owns replacement with the final Central permission model.
 */
final class TemporaryCentralAdminAccess implements CentralAdminAccess
{
    public function allows(User $user): bool
    {
        return ! $user->isSiteAdmin();
    }
}

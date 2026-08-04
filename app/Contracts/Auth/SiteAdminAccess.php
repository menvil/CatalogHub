<?php

declare(strict_types=1);

namespace App\Contracts\Auth;

use App\Models\Site;
use App\Models\User;

interface SiteAdminAccess
{
    public function allows(User $user, ?Site $site = null): bool;

    public function resolveSite(User $user, ?int $requestedSiteId = null): ?Site;
}

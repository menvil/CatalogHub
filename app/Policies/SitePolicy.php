<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

final class SitePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasCatalogHubPermission('central.manage')
            || $user->hasCatalogHubPermission('sites.manage');
    }

    public function view(User $user, Site $site): bool
    {
        return $this->viewAny($user)
            && ($user->hasCatalogHubPermission('central.manage')
                || ($user->site_id !== null && (int) $user->site_id === (int) $site->getKey()));
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Site $site): bool
    {
        return ($user->hasCatalogHubPermission('central.manage')
            || $user->hasCatalogHubPermission('site.settings.manage'))
            && $this->view($user, $site);
    }
}

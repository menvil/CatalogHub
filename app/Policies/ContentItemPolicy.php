<?php

namespace App\Policies;

use App\Models\ContentItem;
use App\Models\User;

final class ContentItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasCatalogHubPermission('site.content.manage');
    }

    public function view(User $user, ContentItem $item): bool
    {
        return $this->viewAny($user) && $this->canAccessSite($user, (int) $item->site_id);
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, ContentItem $item): bool
    {
        return $this->view($user, $item);
    }

    public function delete(User $user, ContentItem $item): bool
    {
        return $this->view($user, $item);
    }

    private function canAccessSite(User $user, int $siteId): bool
    {
        return $user->isSuperAdmin()
            || ($user->site_id !== null && (int) $user->site_id === $siteId);
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Enums\Permission;
use App\Models\Site;
use App\Models\User;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use LogicException;

final class AuthorizationService
{
    public function allowsPanel(User $user, Permission $permission, ?Site $site = null): bool
    {
        $this->ensureLayer($permission, [Permission::CentralPanelAccess, Permission::SitePanelAccess]);

        return $this->allows($user, $permission, $site);
    }

    public function authorizePage(User $user, Permission $permission, ?Site $site = null): void
    {
        $this->ensureLayer($permission, [Permission::CentralPageAccess, Permission::SitePageAccess]);
        $this->authorize($user, $permission, $site);
    }

    public function authorizeMutation(User $user, Permission $permission, ?Site $site = null): void
    {
        $this->ensureLayer($permission, [Permission::CentralMutationExecute, Permission::SiteMutationExecute]);
        $this->authorize($user, $permission, $site);
    }

    /**
     * @template TResult
     *
     * @param  Closure(): TResult  $mutation
     * @return TResult
     */
    public function runMutation(User $user, Permission $permission, Closure $mutation, ?Site $site = null): mixed
    {
        $this->authorizeMutation($user, $permission, $site);

        return $mutation();
    }

    private function authorize(User $user, Permission $permission, ?Site $site): void
    {
        if (! $this->allows($user, $permission, $site)) {
            throw new AuthorizationException;
        }
    }

    private function allows(User $user, Permission $permission, ?Site $site): bool
    {
        if (! $user->isActive() || ! $user->hasCatalogHubPermission($permission->value)) {
            return false;
        }

        if (! str_starts_with($permission->value, 'site.')) {
            return true;
        }

        return $site instanceof Site
            && $site->status->allowsAdministration()
            && $user->memberships()
                ->where('site_id', $site->getKey())
                ->where('is_active', true)
                ->exists();
    }

    /** @param list<Permission> $allowed */
    private function ensureLayer(Permission $permission, array $allowed): void
    {
        if (! in_array($permission, $allowed, true)) {
            throw new LogicException("Permission [{$permission->value}] does not belong to this authorization layer.");
        }
    }
}

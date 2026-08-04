<?php

declare(strict_types=1);

namespace App\Policies;

use App\Contracts\Auth\SiteAdminAccess;
use App\Enums\Permission;
use App\Enums\SiteStatus;
use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use App\Services\Auth\AuthorizationService;
use Illuminate\Database\Eloquent\Builder;

final class SitePanelPolicy implements SiteAdminAccess
{
    public function __construct(private readonly AuthorizationService $authorization) {}

    public function allows(User $user, ?Site $site = null): bool
    {
        if ($site instanceof Site) {
            return $this->authorization->allowsPanel($user, Permission::SitePanelAccess, $site);
        }

        return $user->isActive()
            && $user->hasCatalogHubPermission(Permission::SitePanelAccess->value)
            && $this->memberships($user)->exists();
    }

    public function resolveSite(User $user, ?int $requestedSiteId = null): ?Site
    {
        if (! $user->isActive() || ! $user->hasCatalogHubPermission(Permission::SitePanelAccess->value)) {
            return null;
        }

        $preferredSiteId = $requestedSiteId ?? ($user->site_id !== null ? (int) $user->site_id : null);
        $membership = $this->memberships($user, $preferredSiteId)->first();

        if (! $membership instanceof SiteMembership && $requestedSiteId === null) {
            $membership = $this->memberships($user)->orderBy('site_id')->first();
        }

        return $membership?->site;
    }

    /** @return Builder<SiteMembership> */
    private function memberships(User $user, int|string|null $siteId = null): Builder
    {
        return SiteMembership::query()
            ->where('user_id', $user->getKey())
            ->with('site')
            ->where('is_active', true)
            ->when($siteId !== null, fn (Builder $query) => $query->where('site_id', $siteId))
            ->whereHas('site', fn (Builder $query) => $query->whereIn('status', [
                SiteStatus::Draft,
                SiteStatus::Active,
                SiteStatus::Suspended,
            ]));
    }
}

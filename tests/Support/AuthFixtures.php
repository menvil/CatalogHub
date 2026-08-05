<?php

namespace Tests\Support;

use App\Enums\SiteMembershipRole;
use App\Enums\UserRole;
use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use InvalidArgumentException;

trait AuthFixtures
{
    protected function authorizationSite(string $name): Site
    {
        return Site::factory()->active()->withRuntimeContext()->create(['name' => $name]);
    }

    protected function userForRole(UserRole $role, ?Site $membershipSite = null): User
    {
        if ($role === UserRole::SiteAdmin && $membershipSite instanceof Site) {
            return User::factory()->siteAdmin($membershipSite)->create();
        }

        $membershipRole = $membershipSite instanceof Site ? match ($role) {
            UserRole::SuperAdmin => SiteMembershipRole::SiteAdmin,
            UserRole::Translator => SiteMembershipRole::Translator,
            UserRole::Moderator => SiteMembershipRole::Moderator,
            UserRole::CentralAdmin, UserRole::CatalogEditor => throw new InvalidArgumentException(
                "Role [{$role->value}] cannot be assigned as a site membership role.",
            ),
        } : null;
        $user = User::factory()->create(['role' => $role]);

        if ($membershipSite instanceof Site) {
            SiteMembership::factory()->for($user)->for($membershipSite)->create([
                'role' => $membershipRole,
            ]);
        }

        return $user;
    }
}

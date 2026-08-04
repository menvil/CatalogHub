<?php

namespace Tests\Support;

use App\Enums\SiteMembershipRole;
use App\Enums\UserRole;
use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;

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

        $user = User::factory()->create(['role' => $role]);

        if ($membershipSite instanceof Site) {
            SiteMembership::factory()->for($user)->for($membershipSite)->create([
                'role' => match ($role) {
                    UserRole::Translator => SiteMembershipRole::Translator,
                    UserRole::Moderator => SiteMembershipRole::Moderator,
                    default => SiteMembershipRole::SiteAdmin,
                },
            ]);
        }

        return $user;
    }
}

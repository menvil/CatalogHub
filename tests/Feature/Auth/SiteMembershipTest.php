<?php

namespace Tests\Feature\Auth;

use App\Enums\SiteMembershipRole;
use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_have_active_memberships_in_multiple_sites(): void
    {
        $user = User::factory()->create();
        $first = Site::factory()->create();
        $second = Site::factory()->create();

        SiteMembership::factory()->for($user)->for($first)->create([
            'role' => SiteMembershipRole::SiteAdmin,
        ]);
        SiteMembership::factory()->for($user)->for($second)->create([
            'role' => SiteMembershipRole::Translator,
        ]);

        $this->assertCount(2, $user->memberships);
        $this->assertCount(2, $user->memberSites);
        $this->assertCount(1, $first->memberships);
        $this->assertInstanceOf(SiteMembershipRole::class, $user->memberships->first()->role);
    }

    public function test_duplicate_user_site_membership_is_rejected(): void
    {
        $membership = SiteMembership::factory()->create();

        $this->expectException(QueryException::class);

        SiteMembership::factory()->create([
            'user_id' => $membership->user_id,
            'site_id' => $membership->site_id,
        ]);
    }

    public function test_site_admin_factory_creates_an_explicit_active_membership(): void
    {
        $site = Site::factory()->create();
        $user = User::factory()->siteAdmin($site)->create();

        $this->assertDatabaseHas('site_user_memberships', [
            'user_id' => $user->getKey(),
            'site_id' => $site->getKey(),
            'role' => SiteMembershipRole::SiteAdmin->value,
            'is_active' => true,
        ]);
    }

    public function test_memberships_are_removed_when_the_user_or_site_is_deleted(): void
    {
        $userMembership = SiteMembership::factory()->create();
        $siteMembership = SiteMembership::factory()->create();

        $userMembership->user->delete();
        $siteMembership->site->forceDelete();

        $this->assertDatabaseMissing('site_user_memberships', ['id' => $userMembership->getKey()]);
        $this->assertDatabaseMissing('site_user_memberships', ['id' => $siteMembership->getKey()]);
    }
}

<?php

namespace Tests\Feature\Auth;

use App\Contracts\Auth\SiteAdminAccess;
use App\Enums\SiteMembershipRole;
use App\Enums\UserRole;
use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use App\Policies\SitePanelPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SitePanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_access_contract_uses_the_membership_policy(): void
    {
        $this->assertInstanceOf(SitePanelPolicy::class, app(SiteAdminAccess::class));
    }

    public function test_active_membership_and_site_permission_are_both_required(): void
    {
        $site = Site::factory()->create();
        $siteAdmin = User::factory()->create(['role' => UserRole::SiteAdmin]);
        $centralAdmin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $inactive = User::factory()->create(['role' => UserRole::Moderator]);
        $policy = app(SiteAdminAccess::class);

        SiteMembership::factory()->for($centralAdmin)->for($site)->create();
        SiteMembership::factory()->inactive()->for($inactive)->for($site)->create([
            'role' => SiteMembershipRole::Moderator,
        ]);

        $this->assertFalse($policy->allows($siteAdmin));
        $this->assertFalse($policy->allows($centralAdmin, $site));
        $this->assertFalse($policy->allows($inactive, $site));

        SiteMembership::factory()->for($siteAdmin)->for($site)->create();

        $this->assertTrue($policy->allows($siteAdmin, $site));
    }

    public function test_unassigned_and_tampered_sites_are_denied_server_side(): void
    {
        $assigned = Site::factory()->active()->withRuntimeContext()->create(['name' => 'Assigned site']);
        $other = Site::factory()->active()->withRuntimeContext()->create(['name' => 'Other site']);
        $user = User::factory()->siteAdmin($assigned)->create();

        $this->actingAs($user)
            ->get("/admin/site?site_id={$assigned->getKey()}")
            ->assertOk()
            ->assertSee('Assigned site');

        $this->get("/admin/site?site_id={$other->getKey()}")
            ->assertForbidden()
            ->assertDontSee('Other site');
    }
}

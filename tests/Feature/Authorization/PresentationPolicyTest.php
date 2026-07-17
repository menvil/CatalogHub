<?php

namespace Tests\Feature\Authorization;

use App\Enums\UserRole;
use App\Models\ContentItem;
use App\Models\Lead;
use App\Models\Review;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

final class PresentationPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_gate_is_exact_and_not_a_role_check_in_presentation(): void
    {
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $centralAdmin = User::factory()->centralAdmin()->create();

        $this->assertTrue(Gate::forUser($superAdmin)->allows('system.super-admin'));
        $this->assertFalse(Gate::forUser($centralAdmin)->allows('system.super-admin'));
    }

    public function test_site_policy_preserves_central_and_tenant_access(): void
    {
        $ownSite = Site::factory()->create();
        $otherSite = Site::factory()->create();
        $siteAdmin = User::factory()->siteAdmin($ownSite)->create();
        $centralAdmin = User::factory()->centralAdmin()->create();

        $this->assertTrue(Gate::forUser($siteAdmin)->allows('view', $ownSite));
        $this->assertFalse(Gate::forUser($siteAdmin)->allows('view', $otherSite));
        $this->assertTrue(Gate::forUser($centralAdmin)->allows('view', $otherSite));
    }

    public function test_site_scoped_content_review_and_lead_policies_reject_other_sites(): void
    {
        $ownSite = Site::factory()->create();
        $otherSite = Site::factory()->create();
        $siteAdmin = User::factory()->siteAdmin($ownSite)->create();
        $superAdmin = User::factory()->create(['role' => UserRole::SuperAdmin]);
        $resources = [
            ContentItem::factory()->for($otherSite)->create(),
            Review::factory()->create(['site_id' => $otherSite->id]),
            Lead::factory()->create(['site_id' => $otherSite->id]),
        ];

        foreach ($resources as $resource) {
            $this->assertFalse(Gate::forUser($siteAdmin)->allows('view', $resource));
            $this->assertTrue(Gate::forUser($superAdmin)->allows('view', $resource));
        }
    }
}

<?php

namespace Tests\Feature\Actions;

use App\Actions\Leads\UpdateLeadStatusAction;
use App\Enums\LeadStatus;
use App\Enums\UserRole;
use App\Exceptions\Leads\CannotUpdateLeadException;
use App\Models\Lead;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateLeadStatusActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_admin_can_change_status_for_their_site_lead(): void
    {
        $site = Site::factory()->create();
        $lead = Lead::factory()->create(['site_id' => $site->id, 'status' => LeadStatus::New]);
        $admin = User::factory()->siteAdmin($site)->create();

        $updated = app(UpdateLeadStatusAction::class)->handle($admin, $lead, LeadStatus::Contacted);

        $this->assertSame(LeadStatus::Contacted, $updated->status);
    }

    public function test_super_admin_can_change_any_lead_status(): void
    {
        $lead = Lead::factory()->create(['status' => LeadStatus::New]);
        $admin = User::factory()->create(['role' => UserRole::SuperAdmin]);

        $updated = app(UpdateLeadStatusAction::class)->handle($admin, $lead, LeadStatus::Qualified);

        $this->assertSame(LeadStatus::Qualified, $updated->status);
    }

    public function test_site_admin_cannot_change_another_sites_lead(): void
    {
        $admin = User::factory()->siteAdmin(Site::factory()->create())->create();
        $lead = Lead::factory()->create(['status' => LeadStatus::New]);

        $this->expectException(CannotUpdateLeadException::class);

        app(UpdateLeadStatusAction::class)->handle($admin, $lead, LeadStatus::Closed);
    }
}

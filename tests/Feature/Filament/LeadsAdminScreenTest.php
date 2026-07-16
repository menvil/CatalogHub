<?php

namespace Tests\Feature\Filament;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Enums\UserRole;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\LeadResource\Pages\ListLeads;
use App\Models\Lead;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LeadsAdminScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_admin_sees_leads_for_their_site_only(): void
    {
        $site = Site::factory()->create();
        $otherSite = Site::factory()->create();
        $ownLead = Lead::factory()->create(['site_id' => $site->id, 'name' => 'Ivan Own']);
        $otherLead = Lead::factory()->create(['site_id' => $otherSite->id, 'name' => 'Other Lead']);
        $admin = User::factory()->siteAdmin($site)->create();

        $this->actingAs($admin)
            ->get(LeadResource::getUrl())
            ->assertOk()
            ->assertSee('Ivan Own')
            ->assertDontSee('Other Lead');

        Livewire::actingAs($admin)
            ->test(ListLeads::class)
            ->assertCanSeeTableRecords([$ownLead])
            ->assertCanNotSeeTableRecords([$otherLead]);
    }

    public function test_super_admin_can_see_leads_for_all_sites(): void
    {
        $first = Lead::factory()->create();
        $second = Lead::factory()->create();

        Livewire::actingAs(User::factory()->create(['role' => UserRole::SuperAdmin]))
            ->test(ListLeads::class)
            ->assertCanSeeTableRecords([$first, $second]);
    }

    public function test_user_without_lead_permission_cannot_open_resource(): void
    {
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);

        $this->actingAs($user)
            ->get(LeadResource::getUrl())
            ->assertForbidden();
    }

    public function test_non_super_admin_without_a_site_has_an_empty_lead_query(): void
    {
        Lead::factory()->create();
        $user = User::factory()->create([
            'role' => UserRole::SiteAdmin,
            'site_id' => null,
        ]);

        $this->actingAs($user);

        $this->assertSame(0, LeadResource::getEloquentQuery()->count());
    }

    public function test_leads_resource_is_read_only_and_filters_status_and_type(): void
    {
        $this->assertSame(['index'], array_keys(LeadResource::getPages()));

        $site = Site::factory()->create();
        $visible = Lead::factory()->create([
            'site_id' => $site->id,
            'status' => LeadStatus::New,
            'type' => LeadType::BuyingAdvice,
        ]);
        $hidden = Lead::factory()->create([
            'site_id' => $site->id,
            'status' => LeadStatus::Closed,
            'type' => LeadType::Repair,
        ]);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(ListLeads::class)
            ->filterTable('status', LeadStatus::New->value)
            ->filterTable('type', LeadType::BuyingAdvice->value)
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    public function test_site_admin_can_update_lead_status_from_table(): void
    {
        $site = Site::factory()->create();
        $lead = Lead::factory()->create(['site_id' => $site->id, 'status' => LeadStatus::New]);

        Livewire::actingAs(User::factory()->siteAdmin($site)->create())
            ->test(ListLeads::class)
            ->callTableAction('updateStatus', $lead, data: ['status' => LeadStatus::Contacted->value])
            ->assertHasNoActionErrors();

        $this->assertSame(LeadStatus::Contacted, $lead->fresh()->status);
    }
}

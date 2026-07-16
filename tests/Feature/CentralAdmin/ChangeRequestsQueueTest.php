<?php

namespace Tests\Feature\CentralAdmin;

use App\Filament\Resources\ChangeRequestResource;
use App\Filament\Resources\ChangeRequestResource\Pages\ListChangeRequests;
use App\Models\ChangeRequest;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChangeRequestsQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_render_the_change_requests_queue(): void
    {
        $request = ChangeRequest::factory()->pending()->create();
        $admin = User::factory()->centralAdmin()->create();

        $this->actingAs($admin)
            ->get(ChangeRequestResource::getUrl())
            ->assertOk()
            ->assertSee('Change Requests')
            ->assertSee($request->centralProduct->name);

        Livewire::actingAs($admin)
            ->test(ListChangeRequests::class)
            ->assertCanSeeTableRecords([$request]);
    }

    public function test_queue_can_filter_by_status_and_site(): void
    {
        $site = Site::factory()->create();
        $pending = ChangeRequest::factory()->pending()->create(['site_id' => $site->id]);
        $approved = ChangeRequest::factory()->approved()->create(['site_id' => $site->id]);
        $otherSitePending = ChangeRequest::factory()->pending()->create();

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(ListChangeRequests::class)
            ->filterTable('status', 'pending')
            ->filterTable('site_id', $site->id)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved, $otherSitePending]);
    }

    public function test_change_request_detail_is_available_from_the_queue(): void
    {
        $request = ChangeRequest::factory()->pending()->create([
            'old_value_json' => ['value' => 'Old title'],
            'proposed_value_json' => ['value' => 'New title'],
        ]);

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(ChangeRequestResource::getUrl('view', ['record' => $request]))
            ->assertOk()
            ->assertSee('Old title')
            ->assertSee('New title');
    }

    public function test_site_admin_cannot_open_the_central_queue(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(ChangeRequestResource::getUrl())
            ->assertForbidden();
    }
}

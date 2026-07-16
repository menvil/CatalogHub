<?php

namespace Tests\Feature\CentralAdmin;

use App\Enums\SyncConflictStatus;
use App\Filament\Resources\SyncConflictResource;
use App\Filament\Resources\SyncConflictResource\Pages\ListSyncConflicts;
use App\Models\Site;
use App\Models\SyncConflict;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SyncConflictResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_render_open_sync_conflicts_and_values(): void
    {
        $conflict = SyncConflict::factory()->open()->create([
            'central_value_json' => ['value' => 'Central title'],
            'local_value_json' => ['value' => 'Local title'],
        ]);
        $admin = User::factory()->centralAdmin()->create();

        $this->actingAs($admin)
            ->get(SyncConflictResource::getUrl())
            ->assertOk()
            ->assertSee('Sync Conflicts')
            ->assertSee($conflict->field_path);

        $this->actingAs($admin)
            ->get(SyncConflictResource::getUrl('view', ['record' => $conflict]))
            ->assertOk()
            ->assertSee('Central title')
            ->assertSee('Local title')
            ->assertSee('Use central value')
            ->assertSee('Keep local override')
            ->assertSee('Convert to market override');
    }

    public function test_resolved_conflicts_are_excluded_from_the_open_resolver(): void
    {
        $open = SyncConflict::factory()->open()->create();
        $resolved = SyncConflict::factory()->resolved()->create();

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(ListSyncConflicts::class)
            ->assertCanSeeTableRecords([$open])
            ->assertCanNotSeeTableRecords([$resolved])
            ->assertTableActionExists('useCentralValue', record: $open)
            ->assertTableActionExists('keepLocalOverride', record: $open)
            ->assertTableActionExists('convertToMarketOverride', record: $open);
    }

    public function test_sync_conflict_model_casts_relations_and_factory_states(): void
    {
        $conflict = SyncConflict::factory()->resolved()->create([
            'central_value_json' => ['value' => 144],
            'local_value_json' => ['value' => 165],
            'metadata_json' => ['source' => 'projection'],
        ]);

        $this->assertSame(SyncConflictStatus::Resolved, $conflict->status);
        $this->assertSame(['value' => 144], $conflict->central_value_json);
        $this->assertSame(['value' => 165], $conflict->local_value_json);
        $this->assertSame(['source' => 'projection'], $conflict->metadata_json);
        $this->assertNotNull($conflict->resolved_at);
        $this->assertNotNull($conflict->resolvedBy);
        $this->assertNotNull($conflict->site);
        $this->assertNotNull($conflict->centralProduct);
    }

    public function test_site_admin_cannot_open_sync_conflict_resolver(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(SyncConflictResource::getUrl())
            ->assertForbidden();
    }

    public function test_central_admin_can_use_central_value_from_the_resolver(): void
    {
        $conflict = SyncConflict::factory()->open()->create();

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(ListSyncConflicts::class)
            ->callTableAction('useCentralValue', $conflict)
            ->assertHasNoTableActionErrors();

        $this->assertSame(SyncConflictStatus::Resolved, $conflict->fresh()->status);
        $this->assertSame('use_central_value', $conflict->fresh()->resolution);
    }

    public function test_central_admin_can_keep_local_override_from_the_resolver(): void
    {
        $conflict = SyncConflict::factory()->open()->create();

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(ListSyncConflicts::class)
            ->callTableAction('keepLocalOverride', $conflict)
            ->assertHasNoTableActionErrors();

        $this->assertSame(SyncConflictStatus::Resolved, $conflict->fresh()->status);
        $this->assertSame('keep_local_override', $conflict->fresh()->resolution);
    }
}

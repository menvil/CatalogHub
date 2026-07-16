<?php

namespace Tests\Feature\CentralAdmin;

use App\Filament\Resources\SyncLogResource;
use App\Filament\Resources\SyncLogResource\Pages\ListSyncLogs;
use App\Models\Site;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SyncLogViewerTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_render_and_filter_sync_logs(): void
    {
        $site = Site::factory()->create();
        $completed = SyncLog::factory()->completed()->for($site)->create([
            'operation' => 'rebuild_product_projection',
        ]);
        $failed = SyncLog::factory()->failed()->create([
            'operation' => 'apply_correction',
        ]);
        $admin = User::factory()->centralAdmin()->create();

        $this->actingAs($admin)
            ->get(SyncLogResource::getUrl())
            ->assertOk()
            ->assertSee('Sync Logs')
            ->assertSee('rebuild_product_projection')
            ->assertSee('apply_correction');

        Livewire::actingAs($admin)
            ->test(ListSyncLogs::class)
            ->filterTable('status', 'failed')
            ->assertCanSeeTableRecords([$failed])
            ->assertCanNotSeeTableRecords([$completed]);
    }

    public function test_sync_log_detail_shows_context_error_duration_and_related_entities(): void
    {
        $log = SyncLog::factory()->failed()->create([
            'error_message' => 'Projection rebuild failed.',
            'context_json' => ['change_request_id' => 42],
            'started_at' => now()->subSeconds(5),
            'finished_at' => now(),
        ]);

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(SyncLogResource::getUrl('view', ['record' => $log]))
            ->assertOk()
            ->assertSee('Projection rebuild failed.')
            ->assertSee('change_request_id')
            ->assertSee('42')
            ->assertSee('5 seconds')
            ->assertSee($log->site->name)
            ->assertSee($log->centralProduct->name);
    }

    public function test_sync_log_factory_casts_structured_context_and_timestamps(): void
    {
        $log = SyncLog::factory()->completed()->create([
            'context_json' => ['locales' => ['en', 'de']],
        ]);

        $this->assertSame(['locales' => ['en', 'de']], $log->context_json);
        $this->assertNotNull($log->started_at);
        $this->assertNotNull($log->finished_at);
        $this->assertNotNull($log->site);
        $this->assertNotNull($log->centralProduct);
        $this->assertNotNull($log->triggeredByUser);
    }

    public function test_site_admin_cannot_open_sync_log_viewer(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(SyncLogResource::getUrl())
            ->assertForbidden();
    }
}

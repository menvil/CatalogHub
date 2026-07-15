<?php

namespace Tests\Feature\Admin;

use App\Enums\PriceSourceSyncStatus;
use App\Enums\UserRole;
use App\Filament\Resources\PriceSourceSyncLogResource;
use App\Filament\Resources\PriceSourceSyncLogResource\Pages\ListPriceSourceSyncLogs;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PriceSourceSyncLogsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_price_admin_can_list_filter_and_view_sync_log_errors(): void
    {
        $this->freezeTime();

        $admin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $source = PriceSource::factory()->create(['name' => 'German Feed']);
        $visible = PriceSourceSyncLog::factory()->for($source)->create([
            'status' => PriceSourceSyncStatus::Failed,
            'started_at' => now(),
            'finished_at' => now(),
            'error_message' => 'Remote API unavailable',
        ]);
        $hidden = PriceSourceSyncLog::factory()->create([
            'status' => PriceSourceSyncStatus::Completed,
            'started_at' => now()->subWeek(),
        ]);

        $this->actingAs($admin)
            ->get(PriceSourceSyncLogResource::getUrl())
            ->assertOk()
            ->assertSee('German Feed')
            ->assertSee('failed');

        Livewire::actingAs($admin)
            ->test(ListPriceSourceSyncLogs::class)
            ->filterTable('price_source_id', $source->id)
            ->filterTable('status', PriceSourceSyncStatus::Failed->value)
            ->filterTable('started_at', ['from' => now()->toDateString(), 'until' => now()->toDateString()])
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$hidden]);

        $this->actingAs($admin)
            ->get(PriceSourceSyncLogResource::getUrl('view', ['record' => $visible]))
            ->assertOk()
            ->assertSee('Remote API unavailable');
    }

    public function test_sync_log_resource_is_read_only_and_forbidden_without_price_permission(): void
    {
        $this->assertSame(['index', 'view'], array_keys(PriceSourceSyncLogResource::getPages()));
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);

        $this->actingAs($user)
            ->get(PriceSourceSyncLogResource::getUrl())
            ->assertForbidden();
    }
}

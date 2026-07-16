<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\CatalogSnapshotResource;
use App\Filament\Resources\CatalogSnapshotResource\Pages\ListCatalogSnapshots;
use App\Models\CatalogSnapshot;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ExportHistoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_view_filter_and_open_export_history(): void
    {
        $completed = CatalogSnapshot::factory()->completed()->create([
            'files_json' => [
                'products' => ['path' => 'snapshots/a/products.jsonl', 'file_size' => 100],
                'brands' => ['path' => 'snapshots/a/brands.jsonl', 'file_size' => 50],
            ],
        ]);
        $failed = CatalogSnapshot::factory()->failed()->create([
            'failure_reason' => 'Snapshot disk is unavailable.',
        ]);
        $admin = User::factory()->centralAdmin()->create();

        $this->actingAs($admin)
            ->get(CatalogSnapshotResource::getUrl())
            ->assertOk()
            ->assertSee('Export History')
            ->assertSee('completed')
            ->assertSee('failed');

        Livewire::actingAs($admin)
            ->test(ListCatalogSnapshots::class)
            ->filterTable('status', 'failed')
            ->assertCanSeeTableRecords([$failed])
            ->assertCanNotSeeTableRecords([$completed]);

        $this->actingAs($admin)
            ->get(CatalogSnapshotResource::getUrl('view', ['record' => $completed]))
            ->assertOk()
            ->assertSee($completed->uuid)
            ->assertSee('2')
            ->assertSee('150 B');
    }

    public function test_failed_snapshot_detail_shows_failure_reason(): void
    {
        $snapshot = CatalogSnapshot::factory()->failed()->create([
            'failure_reason' => 'Exporter failed safely.',
        ]);

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(CatalogSnapshotResource::getUrl('view', ['record' => $snapshot]))
            ->assertOk()
            ->assertSee('Exporter failed safely.');
    }

    public function test_site_admin_cannot_open_export_history(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(CatalogSnapshotResource::getUrl())
            ->assertForbidden();
    }
}

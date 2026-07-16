<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\SnapshotGenerationPage;
use App\Models\CatalogSnapshot;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\User;
use App\Services\Export\SnapshotGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class SnapshotGenerationPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_generation_service_runs_selected_exporters_and_completes_snapshot(): void
    {
        Storage::fake('local');
        CentralProduct::factory()->create();
        $admin = User::factory()->centralAdmin()->create();

        $snapshot = app(SnapshotGenerationService::class)->generate(
            $admin,
            ['products', 'categories'],
        );

        $this->assertTrue($snapshot->isCompleted());
        $this->assertSame($admin->id, $snapshot->created_by_user_id);
        $this->assertSame(['products', 'categories'], array_keys($snapshot->files_json));

        foreach ($snapshot->files_json as $file) {
            Storage::disk($snapshot->storage_disk)->assertExists($file['path']);
        }
    }

    public function test_central_admin_can_open_screen_and_generate_snapshot(): void
    {
        Storage::fake('local');
        $admin = User::factory()->centralAdmin()->create();

        $this->actingAs($admin)
            ->get(SnapshotGenerationPage::getUrl())
            ->assertOk()
            ->assertSee('Create Snapshot')
            ->assertSee('not a full database backup');

        Livewire::actingAs($admin)
            ->test(SnapshotGenerationPage::class)
            ->callAction('generateSnapshot')
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('catalog_snapshots', [
            'status' => 'completed',
            'snapshot_type' => 'full',
            'created_by_user_id' => $admin->id,
        ]);
        $this->assertSame(SnapshotGenerationService::sectionKeys(), array_keys(CatalogSnapshot::query()->firstOrFail()->files_json));
    }

    public function test_site_admin_cannot_open_snapshot_generation_screen(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(SnapshotGenerationPage::getUrl())
            ->assertForbidden();
    }
}

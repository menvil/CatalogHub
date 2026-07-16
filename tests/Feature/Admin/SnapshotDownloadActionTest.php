<?php

namespace Tests\Feature\Admin;

use App\Models\CatalogSnapshot;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SnapshotDownloadActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_download_known_completed_snapshot_file(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('snapshots/test/products.jsonl', "{}\n");
        $snapshot = CatalogSnapshot::factory()->completed()->create([
            'storage_disk' => 'local',
            'storage_path' => 'snapshots/test',
            'files_json' => [
                'products' => ['path' => 'snapshots/test/products.jsonl'],
            ],
        ]);

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(route('central.snapshots.download', [$snapshot, 'products']))
            ->assertOk()
            ->assertDownload('products.jsonl');
    }

    public function test_site_admin_cannot_download_snapshot_file(): void
    {
        Storage::fake('local');
        $site = Site::factory()->create();
        $snapshot = CatalogSnapshot::factory()->completed()->create([
            'files_json' => ['products' => ['path' => 'snapshots/test/products.jsonl']],
        ]);

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(route('central.snapshots.download', [$snapshot, 'products']))
            ->assertForbidden();
    }

    public function test_pending_unknown_missing_and_unsafe_snapshot_files_are_not_downloadable(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('snapshots/test/products.jsonl', "{}\n");
        $admin = User::factory()->centralAdmin()->create();
        $pending = CatalogSnapshot::factory()->create([
            'status' => 'pending',
            'files_json' => ['products' => ['path' => 'snapshots/test/products.jsonl']],
        ]);
        $completed = CatalogSnapshot::factory()->completed()->create([
            'files_json' => ['products' => ['path' => 'snapshots/test/products.jsonl']],
        ]);
        $unsafe = CatalogSnapshot::factory()->completed()->create([
            'files_json' => ['products' => ['path' => '../.env']],
        ]);

        $this->actingAs($admin)
            ->get(route('central.snapshots.download', [$pending, 'products']))
            ->assertNotFound();
        $this->actingAs($admin)
            ->get(route('central.snapshots.download', [$completed, 'unknown']))
            ->assertNotFound();
        $this->actingAs($admin)
            ->get(route('central.snapshots.download', [$unsafe, 'products']))
            ->assertNotFound();

        Storage::disk('local')->delete('snapshots/test/products.jsonl');

        $this->actingAs($admin)
            ->get(route('central.snapshots.download', [$completed, 'products']))
            ->assertNotFound();
    }
}

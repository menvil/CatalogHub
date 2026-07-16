<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\CatalogSnapshotResource;
use App\Models\CatalogSnapshot;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RestoreChecklistPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_shows_manual_restore_checklist_without_destructive_action(): void
    {
        $snapshot = CatalogSnapshot::factory()->completed()->create([
            'files_json' => [
                'products' => ['path' => 'snapshots/test/products.jsonl'],
                'media_manifest' => ['path' => 'snapshots/test/media_manifest.jsonl'],
            ],
        ]);

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(CatalogSnapshotResource::getUrl('restore-checklist', ['record' => $snapshot]))
            ->assertOk()
            ->assertSee('Restore Checklist')
            ->assertSee($snapshot->uuid)
            ->assertSee('Database backup')
            ->assertSee('Run checksum verification')
            ->assertSee('Rebuild projections')
            ->assertDontSee('Restore to production now');
    }

    public function test_site_admin_cannot_open_restore_checklist(): void
    {
        $snapshot = CatalogSnapshot::factory()->completed()->create();
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(CatalogSnapshotResource::getUrl('restore-checklist', ['record' => $snapshot]))
            ->assertForbidden();
    }
}

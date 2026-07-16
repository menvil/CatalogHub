<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\MissingMediaReportResource;
use App\Filament\Resources\MissingMediaReportResource\Pages\ListMissingMedia;
use App\Models\MediaManifest;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MissingMediaReportPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_view_missing_originals_and_variants(): void
    {
        $original = MediaManifest::factory()->missing()->create([
            'catalog_snapshot_id' => null,
            'original_path' => 'missing/original.jpg',
            'metadata_json' => [
                'missing_original' => true,
                'missing_variant_count' => 0,
                'missing_paths' => ['media:missing/original.jpg'],
                'last_checked_at' => '2026-07-16T10:00:00+00:00',
            ],
        ]);
        $variant = MediaManifest::factory()->missing()->create([
            'catalog_snapshot_id' => null,
            'original_path' => 'present/original.jpg',
            'metadata_json' => [
                'missing_original' => false,
                'missing_variant_count' => 1,
                'missing_paths' => ['media:missing/card.webp'],
                'last_checked_at' => '2026-07-16T11:00:00+00:00',
            ],
        ]);
        $verified = MediaManifest::factory()->verified()->create(['catalog_snapshot_id' => null]);
        $admin = User::factory()->centralAdmin()->create();

        $this->actingAs($admin)
            ->get(MissingMediaReportResource::getUrl())
            ->assertOk()
            ->assertSee('Missing Media')
            ->assertSee('missing/original.jpg')
            ->assertSee('missing/card.webp');

        Livewire::actingAs($admin)
            ->test(ListMissingMedia::class)
            ->assertCanSeeTableRecords([$original, $variant])
            ->assertCanNotSeeTableRecords([$verified])
            ->filterTable('problem_type', 'original')
            ->assertCanSeeTableRecords([$original])
            ->assertCanNotSeeTableRecords([$variant]);
    }

    public function test_site_admin_cannot_open_missing_media_report(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(MissingMediaReportResource::getUrl())
            ->assertForbidden();
    }
}

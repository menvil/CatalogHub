<?php

namespace Tests\Feature\Admin;

use App\Filament\Pages\CentralDashboard;
use App\Models\CatalogSnapshot;
use App\Models\MediaManifest;
use App\Models\SyncLog;
use App\Models\User;
use App\Services\Backup\BackupStatusWidgetData;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupStatusWidgetTest extends TestCase
{
    use RefreshDatabase;

    public function test_data_service_resolves_snapshot_checksum_and_media_status(): void
    {
        CatalogSnapshot::factory()->completed()->create([
            'completed_at' => now()->subHours(2),
            'files_json' => [
                'products' => ['file_size' => 120],
                'brands' => ['file_size' => 30],
            ],
        ]);
        CatalogSnapshot::factory()->failed()->create(['created_at' => now()->subDay()]);
        MediaManifest::factory()->missing()->create(['catalog_snapshot_id' => null]);
        SyncLog::factory()->completed()->create([
            'operation' => 'verify_snapshot_checksums',
        ]);

        $data = app(BackupStatusWidgetData::class)->resolve();

        $this->assertSame('completed', $data['last_snapshot_status']);
        $this->assertLessThanOrEqual(2, $data['last_snapshot_age_hours']);
        $this->assertSame(150, $data['last_snapshot_size']);
        $this->assertSame('completed', $data['last_checksum_verification_status']);
        $this->assertSame(1, $data['missing_media_count']);
        $this->assertSame(1, $data['failed_exports_count']);
    }

    public function test_central_dashboard_renders_backup_status_widget(): void
    {
        CatalogSnapshot::factory()->completed()->create();

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(CentralDashboard::getUrl())
            ->assertOk()
            ->assertSee('Backup status')
            ->assertSee('Last snapshot')
            ->assertSee('Missing media');
    }
}

<?php

namespace Tests\Feature\CentralAdmin;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Filament\Pages\SyncDashboard;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use App\Models\SiteProductProjection;
use App\Models\SyncLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SyncDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_can_render_sync_dashboard_metrics_and_recent_runs(): void
    {
        $site = Site::factory()->create(['name' => 'Sofia Catalog']);
        $product = CentralProduct::factory()->create(['version' => 3]);
        SiteProduct::factory()->for($site)->for($product, 'centralProduct')->create(['published_version' => 1]);
        SiteProductProjection::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'central_product_id' => $product->id,
            'slug' => 'failed-product',
            'status' => ProjectionStatus::Failed,
            'payload_json' => [],
            'failed_at' => now(),
        ]);
        DB::table('sync_conflicts')->insert([
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'entity_type' => 'central_product',
            'entity_id' => $product->id,
            'field_path' => 'name',
            'conflict_type' => 'local_override',
            'status' => 'open',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        SyncLog::query()->create([
            'site_id' => $site->id,
            'central_product_id' => $product->id,
            'operation' => 'product_rebuild',
            'status' => 'completed',
            'triggered_by' => 'user',
            'finished_at' => now(),
            'affected_count' => 1,
        ]);

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(SyncDashboard::getUrl())
            ->assertOk()
            ->assertSee('Sync Dashboard')
            ->assertSee('Stale products')
            ->assertSee('Failed projections')
            ->assertSee('Open conflicts')
            ->assertSee('product_rebuild')
            ->assertSee('Sofia Catalog');
    }

    public function test_site_admin_cannot_open_sync_dashboard(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(SyncDashboard::getUrl())
            ->assertForbidden();
    }
}

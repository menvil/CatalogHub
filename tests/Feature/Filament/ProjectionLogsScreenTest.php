<?php

namespace Tests\Feature\Filament;

use App\Domains\Projections\SiteSyncService;
use App\Enums\CentralProductStatus;
use App\Enums\UserRole;
use App\Filament\Resources\ProjectionConflictResource;
use App\Filament\Resources\ProjectionJobResource;
use App\Filament\Resources\ProjectionLogResource;
use App\Filament\Resources\ProjectionLogResource\Pages\ListProjectionLogs;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\ProjectionConflict;
use App\Models\ProjectionLog;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectionLogsScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_editor_can_view_jobs_logs_and_conflicts(): void
    {
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create(['status' => CentralProductStatus::Active]);
        app(SiteSyncService::class)->syncProduct($site, $product, 'en');
        ProjectionConflict::create([
            'site_id' => $site->id,
            'locale' => 'en',
            'entity_type' => 'product',
            'entity_id' => $product->id,
            'conflict_type' => 'missing_translation',
            'severity' => 'medium',
            'status' => 'open',
            'message' => 'Product title translation is missing.',
        ]);

        $this->actingAs($user)
            ->get(ProjectionJobResource::getUrl())
            ->assertOk()
            ->assertSee('product')
            ->assertSee('completed');
        $this->actingAs($user)
            ->get(ProjectionLogResource::getUrl())
            ->assertOk()
            ->assertSee('started')
            ->assertSee('completed');
        $this->actingAs($user)
            ->get(ProjectionConflictResource::getUrl())
            ->assertOk()
            ->assertSee('missing_translation');
    }

    public function test_log_filters_scope_by_site_level_job_status_locale_and_entity(): void
    {
        $user = User::factory()->create(['role' => UserRole::CatalogEditor]);
        $site = Site::factory()->create();
        $product = CentralProduct::factory()->create(['status' => CentralProductStatus::Active]);
        app(SiteSyncService::class)->syncProduct($site, $product, 'en');
        $visible = ProjectionLog::query()->where('event', 'completed')->firstOrFail();
        $hidden = ProjectionLog::create([
            'site_id' => Site::factory()->create()->id,
            'level' => 'error',
            'event' => 'failed',
            'message' => 'Different site failure.',
            'entity_type' => 'category',
            'entity_id' => 999,
        ]);

        Livewire::actingAs($user)
            ->test(ListProjectionLogs::class)
            ->filterTable('site_id', $site->id)
            ->filterTable('level', 'info')
            ->filterTable('job_status', 'completed')
            ->filterTable('locale', 'en')
            ->filterTable('entity_type', 'product')
            ->assertCanSeeTableRecords([$visible])
            ->assertCanNotSeeTableRecords([$hidden]);
    }

    public function test_site_admin_cannot_view_projection_audit_resources(): void
    {
        $user = User::factory()->create(['role' => UserRole::SiteAdmin]);

        $this->actingAs($user)->get(ProjectionLogResource::getUrl())->assertForbidden();
        $this->actingAs($user)->get(ProjectionConflictResource::getUrl())->assertForbidden();
    }

    public function test_audit_resources_are_read_only(): void
    {
        foreach ([ProjectionJobResource::class, ProjectionLogResource::class, ProjectionConflictResource::class] as $resource) {
            $pages = $resource::getPages();

            $this->assertArrayHasKey('index', $pages);
            $this->assertArrayHasKey('view', $pages);
            $this->assertArrayNotHasKey('create', $pages);
            $this->assertArrayNotHasKey('edit', $pages);
        }
    }
}

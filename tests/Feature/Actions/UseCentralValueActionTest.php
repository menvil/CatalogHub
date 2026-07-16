<?php

namespace Tests\Feature\Actions;

use App\Actions\Sync\UseCentralValueAction;
use App\Enums\SyncConflictStatus;
use App\Exceptions\Sync\CannotResolveSyncConflictException;
use App\Models\Site;
use App\Models\SiteOverride;
use App\Models\SyncConflict;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UseCentralValueActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_conflict_by_removing_matching_local_override(): void
    {
        $conflict = SyncConflict::factory()->open()->create(['field_path' => 'local_title']);
        SiteOverride::query()->create([
            'site_id' => $conflict->site_id,
            'entity_type' => 'product',
            'entity_id' => $conflict->central_product_id,
            'field' => 'local_title',
            'locale_code' => '',
            'value_json' => ['value' => 'Local title'],
            'status' => 'active',
        ]);
        $admin = User::factory()->centralAdmin()->create();

        $resolved = app(UseCentralValueAction::class)->handle($admin, $conflict);

        $this->assertSame(SyncConflictStatus::Resolved, $resolved->status);
        $this->assertSame('use_central_value', $resolved->resolution);
        $this->assertTrue($resolved->resolvedBy->is($admin));
        $this->assertNotNull($resolved->resolved_at);
        $this->assertDatabaseCount('site_overrides', 0);
        $this->assertDatabaseHas('sync_logs', [
            'operation' => 'resolve_sync_conflict',
            'status' => 'completed',
            'site_id' => $conflict->site_id,
            'central_product_id' => $conflict->central_product_id,
        ]);
    }

    public function test_already_resolved_conflict_cannot_be_resolved_again(): void
    {
        $this->expectException(CannotResolveSyncConflictException::class);

        app(UseCentralValueAction::class)->handle(
            User::factory()->centralAdmin()->create(),
            SyncConflict::factory()->resolved()->create(),
        );
    }

    public function test_site_admin_cannot_use_central_resolution(): void
    {
        $site = Site::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(UseCentralValueAction::class)->handle(
            User::factory()->siteAdmin($site)->create(),
            SyncConflict::factory()->open()->create(),
        );
    }
}

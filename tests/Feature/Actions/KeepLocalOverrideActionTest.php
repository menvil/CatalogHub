<?php

namespace Tests\Feature\Actions;

use App\Actions\Sync\KeepLocalOverrideAction;
use App\Enums\SyncConflictStatus;
use App\Exceptions\Sync\CannotResolveSyncConflictException;
use App\Models\Site;
use App\Models\SyncConflict;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KeepLocalOverrideActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_conflict_by_preserving_local_value_as_active_override(): void
    {
        $conflict = SyncConflict::factory()->open()->create([
            'field_path' => 'local_title',
            'local_value_json' => ['value' => 'Local title'],
            'metadata_json' => ['locale_code' => 'de-DE'],
        ]);
        $admin = User::factory()->centralAdmin()->create();

        $resolved = app(KeepLocalOverrideAction::class)->handle($admin, $conflict);

        $this->assertSame(SyncConflictStatus::Resolved, $resolved->status);
        $this->assertSame('keep_local_override', $resolved->resolution);
        $this->assertTrue($resolved->resolvedBy->is($admin));
        $this->assertDatabaseHas('site_overrides', [
            'site_id' => $conflict->site_id,
            'entity_type' => 'product',
            'entity_id' => $conflict->central_product_id,
            'field' => 'local_title',
            'locale_code' => 'de-DE',
            'value_json' => json_encode(['value' => 'Local title']),
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('sync_logs', [
            'operation' => 'resolve_sync_conflict',
            'status' => 'completed',
            'site_id' => $conflict->site_id,
        ]);
    }

    public function test_already_resolved_conflict_cannot_keep_local_override(): void
    {
        $this->expectException(CannotResolveSyncConflictException::class);

        app(KeepLocalOverrideAction::class)->handle(
            User::factory()->centralAdmin()->create(),
            SyncConflict::factory()->resolved()->create(),
        );
    }

    public function test_site_admin_cannot_keep_local_override_from_central_resolver(): void
    {
        $site = Site::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(KeepLocalOverrideAction::class)->handle(
            User::factory()->siteAdmin($site)->create(),
            SyncConflict::factory()->open()->create(),
        );
    }
}

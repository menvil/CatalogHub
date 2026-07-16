<?php

namespace Tests\Feature\Actions;

use App\Actions\Sync\ConvertToMarketOverrideAction;
use App\Enums\SyncConflictStatus;
use App\Exceptions\Sync\CannotResolveSyncConflictException;
use App\Models\Site;
use App\Models\SiteOverride;
use App\Models\SyncConflict;
use App\Models\User;
use App\Services\Sites\SiteOverrideResolver;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConvertToMarketOverrideActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_converts_site_value_to_market_override_and_resolves_conflict(): void
    {
        $conflict = SyncConflict::factory()->open()->create([
            'field_path' => 'local_title',
            'local_value_json' => ['value' => 'Market title'],
            'metadata_json' => ['locale_code' => 'de-DE'],
        ]);
        SiteOverride::query()->create([
            'site_id' => $conflict->site_id,
            'entity_type' => 'product',
            'entity_id' => $conflict->central_product_id,
            'field' => 'local_title',
            'locale_code' => 'de-DE',
            'value_json' => ['value' => 'Site title'],
            'status' => 'active',
        ]);
        $admin = User::factory()->centralAdmin()->create();

        $resolved = app(ConvertToMarketOverrideAction::class)->handle($admin, $conflict);

        $this->assertTrue(Schema::hasColumn('site_overrides', 'market_id'));
        $this->assertSame(SyncConflictStatus::Resolved, $resolved->status);
        $this->assertSame('convert_to_market_override', $resolved->resolution);
        $this->assertDatabaseMissing('site_overrides', [
            'site_id' => $conflict->site_id,
            'entity_type' => 'product',
            'entity_id' => $conflict->central_product_id,
            'field' => 'local_title',
        ]);
        $this->assertDatabaseHas('site_overrides', [
            'site_id' => null,
            'market_id' => $conflict->site->market_id,
            'entity_type' => 'product',
            'entity_id' => $conflict->central_product_id,
            'field' => 'local_title',
            'locale_code' => 'de-DE',
            'value_json' => json_encode(['value' => 'Market title']),
            'status' => 'active',
        ]);
        $marketOverride = SiteOverride::query()->whereNull('site_id')->firstOrFail();
        $this->assertTrue($marketOverride->market->is($conflict->site->market));
        $secondMarketSite = Site::factory()->create(['market_id' => $conflict->site->market_id]);
        $this->assertSame('Market title', app(SiteOverrideResolver::class)->resolve(
            $secondMarketSite,
            'product',
            $conflict->central_product_id,
            'local_title',
            'de-DE',
        ));
        $this->assertDatabaseHas('sync_logs', [
            'operation' => 'resolve_sync_conflict',
            'status' => 'completed',
        ]);
    }

    public function test_already_resolved_conflict_cannot_convert_to_market_override(): void
    {
        $this->expectException(CannotResolveSyncConflictException::class);

        app(ConvertToMarketOverrideAction::class)->handle(
            User::factory()->centralAdmin()->create(),
            SyncConflict::factory()->resolved()->create(),
        );
    }

    public function test_site_admin_cannot_convert_to_market_override(): void
    {
        $site = Site::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(ConvertToMarketOverrideAction::class)->handle(
            User::factory()->siteAdmin($site)->create(),
            SyncConflict::factory()->open()->create(),
        );
    }
}

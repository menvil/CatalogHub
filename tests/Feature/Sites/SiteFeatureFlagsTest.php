<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\UpdateSiteFeaturesAction;
use App\Filament\Resources\SiteResource\Pages\EditSite;
use App\Filament\Resources\SiteResource\RelationManagers\SiteFeaturesRelationManager;
use App\Models\Site;
use App\Models\SiteFeature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SiteFeatureFlagsTest extends TestCase
{
    use RefreshDatabase;

    public function test_feature_flags_are_upserted_without_duplicates(): void
    {
        $site = Site::factory()->create();
        $action = app(UpdateSiteFeaturesAction::class);
        $action->handle($site, ['reviews' => ['is_enabled' => true, 'config_json' => ['moderation' => true]]]);
        $action->handle($site, ['reviews' => ['is_enabled' => false, 'config_json' => ['moderation' => false]]]);

        $this->assertDatabaseCount('site_features', 1);
        $feature = $site->features()->sole();
        $this->assertFalse($feature->is_enabled);
        $this->assertSame(['moderation' => false], $feature->config_json);
    }

    public function test_relation_manager_reports_duplicate_feature_as_validation_error(): void
    {
        $site = Site::factory()->create();
        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'reviews',
            'is_enabled' => true,
        ]);

        Livewire::actingAs(User::factory()->centralAdmin()->create())
            ->test(SiteFeaturesRelationManager::class, [
                'ownerRecord' => $site,
                'pageClass' => EditSite::class,
            ])
            ->callTableAction('create', data: [
                'feature_key' => 'reviews',
                'is_enabled' => false,
            ])
            ->assertHasTableActionErrors(['feature_key' => 'unique']);

        $this->assertDatabaseCount('site_features', 1);
    }

    public function test_malformed_feature_payload_is_rejected_without_mutation(): void
    {
        $site = Site::factory()->create();
        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'reviews',
            'is_enabled' => true,
        ]);

        try {
            /** @phpstan-ignore-next-line Intentionally malformed payload exercises runtime validation. */
            app(UpdateSiteFeaturesAction::class)->handle($site, ['reviews' => []]);

            $this->fail('A feature without is_enabled was accepted.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('features.reviews.is_enabled', $exception->errors());
        }

        $this->assertTrue($site->features()->sole()->is_enabled);
    }

    public function test_status_only_update_preserves_existing_feature_config(): void
    {
        $site = Site::factory()->create();
        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'reviews',
            'is_enabled' => true,
            'config_json' => ['moderation' => true],
        ]);

        app(UpdateSiteFeaturesAction::class)->handle($site, [
            'reviews' => ['is_enabled' => false],
        ]);

        $feature = $site->features()->sole();
        $this->assertFalse($feature->is_enabled);
        $this->assertSame(['moderation' => true], $feature->config_json);
    }

    public function test_explicit_null_clears_existing_feature_config(): void
    {
        $site = Site::factory()->create();
        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'reviews',
            'is_enabled' => true,
            'config_json' => ['moderation' => true],
        ]);

        app(UpdateSiteFeaturesAction::class)->handle($site, [
            'reviews' => ['is_enabled' => true, 'config_json' => null],
        ]);

        $this->assertNull($site->features()->sole()->config_json);
    }
}

<?php

namespace Tests\Feature\Sites;

use App\Actions\Sites\UpdateSiteFeaturesAction;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}

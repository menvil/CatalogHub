<?php

namespace Tests\Feature\Sites;

use App\Filament\Resources\SiteResource\Pages\SiteDashboard;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteFeature;
use App\Models\SiteProduct;
use App\Models\User;
use App\Services\Sites\SiteDashboardMetrics;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiteDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_metrics_are_scoped_to_selected_site(): void
    {
        $site = Site::factory()->create();
        $other = Site::factory()->create();
        SiteProduct::query()->create(['site_id' => $site->id, 'central_product_id' => CentralProduct::factory()->create()->id, 'visibility' => 'visible']);
        SiteProduct::query()->create(['site_id' => $site->id, 'central_product_id' => CentralProduct::factory()->create()->id, 'visibility' => 'hidden']);
        SiteProduct::query()->create(['site_id' => $other->id, 'central_product_id' => CentralProduct::factory()->create()->id, 'visibility' => 'visible']);
        SiteFeature::query()->create(['site_id' => $site->id, 'feature_key' => 'reviews', 'is_enabled' => true]);
        DB::table('site_locales')->insert(['site_id' => $site->id, 'locale_code' => 'en-US', 'is_default' => true, 'is_enabled' => true, 'position' => 0, 'created_at' => now(), 'updated_at' => now()]);

        $metrics = app(SiteDashboardMetrics::class)->metricsFor($site);
        $this->assertSame(1, $metrics['visible_products']);
        $this->assertSame(1, $metrics['hidden_products']);
        $this->assertSame(1, $metrics['enabled_locales']);
        $this->assertSame(1, $metrics['enabled_features']);
        $this->assertSame(1, $metrics['products_without_local_seo']);
    }

    public function test_dashboard_page_renders_placeholders(): void
    {
        $site = Site::factory()->create();
        $this->actingAs(User::factory()->centralAdmin()->create())->get(SiteDashboard::getUrl(['record' => $site]))->assertOk()->assertSee('Site Dashboard')->assertSee('Products without prices')->assertSee('Sync status');
    }
}

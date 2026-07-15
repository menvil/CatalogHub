<?php

namespace Tests\Feature\SiteAdmin;

use App\Enums\PriceSourceSyncStatus;
use App\Filament\Resources\SiteResource\Pages\SiteDashboard;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\Site;
use App\Models\User;
use App\Services\Pricing\PriceUpdateQueueStatusBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceUpdateQueueMonitorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_builds_site_scoped_price_update_queue_status(): void
    {
        [$site, $source] = $this->scenario();

        $status = app(PriceUpdateQueueStatusBuilder::class)->build($site);

        $this->assertSame(1, $status->pendingJobsCount);
        $this->assertSame(1, $status->runningJobsCount);
        $this->assertSame(1, $status->failedJobsCount);
        $this->assertNotNull($status->lastSyncAt);
        $this->assertSame($source->name, $status->recentFailedSource);
        $this->assertSame('Provider timeout', $status->recentFailureMessage);
    }

    public function test_site_admin_dashboard_renders_the_price_update_queue_monitor(): void
    {
        [$site, $source] = $this->scenario();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(SiteDashboard::getUrl(['record' => $site]))
            ->assertOk()
            ->assertSee('Price Update Queue Monitor')
            ->assertSee('Pending jobs')
            ->assertSee('Running jobs')
            ->assertSee('Failed jobs')
            ->assertSee($source->name)
            ->assertSee('View sync status');
    }

    /** @return array{Site, PriceSource} */
    private function scenario(): array
    {
        $site = Site::factory()->create();
        $source = PriceSource::factory()->active()->create([
            'market_id' => $site->market_id,
            'name' => 'Monitored Feed',
        ]);
        $disabled = PriceSource::factory()->active()->create(['market_id' => $site->market_id]);
        $site->priceSources()->attach([
            $source->id => ['enabled' => true],
            $disabled->id => ['enabled' => false],
        ]);
        PriceSourceSyncLog::factory()->create([
            'price_source_id' => $source->id,
            'status' => PriceSourceSyncStatus::Queued,
        ]);
        PriceSourceSyncLog::factory()->running()->create([
            'price_source_id' => $source->id,
        ]);
        PriceSourceSyncLog::factory()->create([
            'price_source_id' => $source->id,
            'status' => PriceSourceSyncStatus::Failed,
            'started_at' => now()->subMinutes(10),
            'finished_at' => now()->subMinutes(5),
            'error_message' => 'Provider timeout',
        ]);
        PriceSourceSyncLog::factory()->create([
            'price_source_id' => $disabled->id,
            'status' => PriceSourceSyncStatus::Failed,
            'error_message' => 'Disabled source failure',
        ]);

        return [$site, $source];
    }
}

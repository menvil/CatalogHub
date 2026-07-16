<?php

namespace Tests\Feature\Health;

use App\Enums\PriceSourceStatus;
use App\Enums\PriceSourceSyncStatus;
use App\Enums\PriceSourceUpdateFrequency;
use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Services\Health\ExternalSourceHealthCheck;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalSourceHealthCheckTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_reports_healthy_recent_import_and_price_sources_without_network_calls(): void
    {
        $importSource = ImportSource::factory()->create(['type' => ImportSource::TYPE_API]);
        ImportBatch::factory()->for($importSource, 'source')->create([
            'status' => 'completed',
            'finished_at' => now(),
        ]);
        $priceSource = PriceSource::factory()->active()->create([
            'update_frequency' => PriceSourceUpdateFrequency::Hourly,
            'last_sync_at' => now(),
        ]);
        PriceSourceSyncLog::factory()->for($priceSource)->create([
            'status' => PriceSourceSyncStatus::Completed,
            'finished_at' => now(),
        ]);

        $result = app(ExternalSourceHealthCheck::class)->run();

        $this->assertSame('ok', $result->status);
        $this->assertSame(1, $result->details['active_import_sources']);
        $this->assertSame(1, $result->details['active_price_sources']);
    }

    public function test_it_reports_repeated_price_source_failures_as_an_error(): void
    {
        $source = PriceSource::factory()->active()->create([
            'update_frequency' => PriceSourceUpdateFrequency::Hourly,
            'last_sync_at' => now(),
        ]);

        PriceSourceSyncLog::factory()->count(3)->for($source)->create([
            'status' => PriceSourceSyncStatus::Failed,
            'finished_at' => now(),
        ]);

        $result = app(ExternalSourceHealthCheck::class)->run();

        $this->assertSame('error', $result->status);
        $this->assertSame(1, $result->details['error_price_sources']);
    }

    public function test_it_warns_for_scheduled_sources_that_have_never_completed(): void
    {
        ImportSource::factory()->create(['type' => ImportSource::TYPE_SCRAPER]);
        PriceSource::factory()->create(['status' => PriceSourceStatus::Inactive]);

        $result = app(ExternalSourceHealthCheck::class)->run();

        $this->assertSame('warning', $result->status);
        $this->assertSame(1, $result->details['warning_import_sources']);
        $this->assertSame(0, $result->details['active_price_sources']);
    }
}

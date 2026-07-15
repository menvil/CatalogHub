<?php

namespace Tests\Feature\Pricing;

use App\Enums\PriceSourceStatus;
use App\Enums\PriceSourceSyncStatus;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Services\Pricing\PriceSourceSyncStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceSourceSyncStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_starts_and_completes_source_sync_consistently(): void
    {
        $source = PriceSource::factory()->active()->create();
        $log = PriceSourceSyncLog::factory()->for($source)->create();
        $service = app(PriceSourceSyncStatusService::class);

        $service->start($source, $log);
        $this->assertSame(PriceSourceSyncStatus::Running, $log->fresh()->status);
        $this->assertNotNull($log->fresh()->started_at);

        $service->complete($source, $log, ['items_fetched' => 3, 'items_updated' => 2]);

        $this->assertSame(PriceSourceStatus::Active, $source->fresh()->status);
        $this->assertNotNull($source->fresh()->last_sync_at);
        $this->assertSame(PriceSourceSyncStatus::Completed, $log->fresh()->status);
        $this->assertSame(3, $log->fresh()->items_fetched);
        $this->assertSame(2, $log->fresh()->items_updated);
        $this->assertNotNull($log->fresh()->finished_at);
    }

    public function test_failure_marks_source_and_log_and_records_context(): void
    {
        $source = PriceSource::factory()->active()->create();
        $log = PriceSourceSyncLog::factory()->running()->for($source)->create();

        app(PriceSourceSyncStatusService::class)->fail(
            $source,
            $log,
            'API error',
            ['stage' => 'fetch'],
        );

        $this->assertSame(PriceSourceStatus::Failed, $source->fresh()->status);
        $this->assertSame(PriceSourceSyncStatus::Failed, $log->fresh()->status);
        $this->assertSame('API error', $log->fresh()->error_message);
        $this->assertSame(['stage' => 'fetch'], $log->fresh()->metadata);
        $this->assertNotNull($log->fresh()->finished_at);
    }

    public function test_partial_completion_marks_source_delayed(): void
    {
        $source = PriceSource::factory()->active()->create();
        $log = PriceSourceSyncLog::factory()->running()->for($source)->create();

        app(PriceSourceSyncStatusService::class)->partiallyComplete(
            $source,
            $log,
            ['items_normalized' => 2],
            'One row failed.',
        );

        $this->assertSame(PriceSourceStatus::Delayed, $source->fresh()->status);
        $this->assertSame(PriceSourceSyncStatus::PartiallyCompleted, $log->fresh()->status);
        $this->assertSame('One row failed.', $log->fresh()->error_message);
    }
}

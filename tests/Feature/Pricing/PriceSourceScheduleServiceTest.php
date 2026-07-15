<?php

namespace Tests\Feature\Pricing;

use App\Enums\PriceSourceStatus;
use App\Enums\PriceSourceUpdateFrequency;
use App\Jobs\Pricing\FetchExternalOffersJob;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Services\Pricing\PriceSourceScheduleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class PriceSourceScheduleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_detects_only_active_due_sources_by_update_frequency(): void
    {
        $due = PriceSource::factory()->active()->create([
            'update_frequency' => PriceSourceUpdateFrequency::Hourly,
            'last_sync_at' => now()->subHours(2),
        ]);
        PriceSource::factory()->active()->create([
            'update_frequency' => PriceSourceUpdateFrequency::Hourly,
            'last_sync_at' => now()->subMinutes(30),
        ]);
        PriceSource::factory()->active()->create([
            'update_frequency' => PriceSourceUpdateFrequency::Manual,
            'last_sync_at' => now()->subYear(),
        ]);
        PriceSource::factory()->create([
            'status' => PriceSourceStatus::Inactive,
            'update_frequency' => PriceSourceUpdateFrequency::Daily,
            'last_sync_at' => null,
        ]);

        $sources = app(PriceSourceScheduleService::class)->dueSources();

        $this->assertCount(1, $sources);
        $this->assertTrue($due->is($sources->first()));
        $this->assertSame(PriceSourceUpdateFrequency::Hourly, $due->fresh()->update_frequency);
    }

    public function test_command_dispatches_every_due_source(): void
    {
        Bus::fake();
        $source = PriceSource::factory()->active()->create([
            'update_frequency' => PriceSourceUpdateFrequency::Daily,
            'last_sync_at' => null,
        ]);

        $exitCode = Artisan::call('pricing:sync-due-sources');

        $this->assertSame(0, $exitCode);
        $this->assertSame(1, $source->syncLogs()->count());
        Bus::assertDispatched(FetchExternalOffersJob::class);
    }

    public function test_command_reports_one_source_failure_and_continues_queueing_other_due_sources(): void
    {
        Bus::fake();
        $failingSource = PriceSource::factory()->active()->create([
            'name' => 'Broken Feed',
            'update_frequency' => PriceSourceUpdateFrequency::Daily,
            'last_sync_at' => null,
        ]);
        $healthySource = PriceSource::factory()->active()->create([
            'name' => 'Healthy Feed',
            'update_frequency' => PriceSourceUpdateFrequency::Daily,
            'last_sync_at' => null,
        ]);
        $event = 'eloquent.creating: '.PriceSourceSyncLog::class;

        Event::listen($event, function (PriceSourceSyncLog $log) use ($failingSource): void {
            if ($log->price_source_id === $failingSource->id) {
                throw new RuntimeException('Database unavailable.');
            }
        });

        try {
            $exitCode = Artisan::call('pricing:sync-due-sources');
        } finally {
            Event::forget($event);
        }

        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertSame(0, $failingSource->syncLogs()->count());
        $this->assertSame(1, $healthySource->syncLogs()->count());
        $this->assertStringContainsString('Broken Feed', $output);
        $this->assertStringContainsString('Queued 1 due price source sync(s).', $output);
        Bus::assertDispatchedTimes(FetchExternalOffersJob::class, 1);
    }
}

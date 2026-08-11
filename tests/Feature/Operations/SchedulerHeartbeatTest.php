<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Models\OperationalHeartbeat;
use App\Services\Operations\SchedulerHealthService;
use App\Services\Operations\SchedulerHeartbeatService;
use App\Support\Operations\HealthStatus;
use Carbon\CarbonImmutable;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SchedulerHeartbeatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('operations.scheduler_heartbeat_stale_after', 300);
    }

    public function test_command_updates_one_scheduler_heartbeat_record(): void
    {
        CarbonImmutable::setTestNow('2026-08-11T12:00:00+00:00');
        $this->artisan('operations:record-scheduler-heartbeat')->assertSuccessful();

        CarbonImmutable::setTestNow('2026-08-11T12:01:00+00:00');
        $this->artisan('operations:record-scheduler-heartbeat')->assertSuccessful();

        self::assertDatabaseCount('operational_heartbeats', 1);
        self::assertDatabaseHas('operational_heartbeats', [
            'name' => SchedulerHeartbeatService::NAME,
            'last_ran_at' => '2026-08-11 12:01:00',
        ]);
    }

    public function test_scheduler_health_detects_stale_and_fresh_heartbeats(): void
    {
        CarbonImmutable::setTestNow('2026-08-11T12:00:00+00:00');
        OperationalHeartbeat::query()->create([
            'name' => SchedulerHeartbeatService::NAME,
            'last_ran_at' => now()->subSeconds(301),
        ]);

        self::assertSame(HealthStatus::Stale, app(SchedulerHealthService::class)->inspect()->status);

        OperationalHeartbeat::query()->where('name', SchedulerHeartbeatService::NAME)->update([
            'last_ran_at' => now()->subSeconds(300),
        ]);

        self::assertSame(HealthStatus::Healthy, app(SchedulerHealthService::class)->inspect()->status);

        OperationalHeartbeat::query()->where('name', SchedulerHeartbeatService::NAME)->update([
            'last_ran_at' => now(),
        ]);

        self::assertSame(HealthStatus::Healthy, app(SchedulerHealthService::class)->inspect()->status);
    }

    public function test_scheduler_heartbeat_is_registered_once_per_schedule(): void
    {
        $events = array_filter(
            app(Schedule::class)->events(),
            static fn (Event $event): bool => str_contains($event->getSummaryForDisplay(), 'operations:record-scheduler-heartbeat'),
        );

        self::assertCount(1, $events);
        $event = array_values($events)[0];
        self::assertSame('* * * * *', $event->getExpression());
        self::assertTrue($event->withoutOverlapping);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit\Operations;

use App\Models\JobIdempotencyRecord;
use App\Services\Operations\QueueHealthService;
use App\Support\Operations\HealthStatus;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

final class QueueHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('queue.default', 'database');
        config()->set('queue.failed.database', config('database.default'));
        config()->set('operations.queue_heartbeat_stale_after', 300);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_it_reports_a_healthy_queue_with_a_recent_heartbeat(): void
    {
        JobIdempotencyRecord::query()->create([
            'idempotency_key' => 'foundation-heartbeat:recent',
            'completed_at' => now()->subSecond(),
        ]);

        $health = app(QueueHealthService::class)->inspect();

        self::assertSame(HealthStatus::Healthy, $health->status);
        self::assertSame(0, $health->recentFailedJobs);
        self::assertNotNull($health->lastHeartbeatAt);
    }

    public function test_it_reports_recent_failed_jobs(): void
    {
        CarbonImmutable::setTestNow('2026-08-11T12:00:00+00:00');
        JobIdempotencyRecord::query()->create([
            'idempotency_key' => 'foundation-heartbeat:recent',
            'completed_at' => now(),
        ]);
        $recentUuid = (string) Str::uuid();
        DB::table('failed_jobs')->insert([
            'uuid' => $recentUuid,
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Test failure',
            'failed_at' => now()->subDay(),
        ]);
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => '{}',
            'exception' => 'Expired test failure',
            'failed_at' => now()->subDay()->subSecond(),
        ]);

        $health = app(QueueHealthService::class)->inspect();

        self::assertSame(HealthStatus::Failed, $health->status);
        self::assertSame(1, $health->recentFailedJobs);

        DB::table('failed_jobs')->where('uuid', $recentUuid)->delete();
        $health = app(QueueHealthService::class)->inspect();

        self::assertSame(HealthStatus::Healthy, $health->status);
        self::assertSame(0, $health->recentFailedJobs);
    }

    public function test_it_reports_a_missing_or_old_heartbeat_as_stale(): void
    {
        CarbonImmutable::setTestNow('2026-08-11T12:00:00+00:00');
        $withoutHeartbeat = app(QueueHealthService::class)->inspect();

        self::assertSame(HealthStatus::Stale, $withoutHeartbeat->status);

        JobIdempotencyRecord::query()->create([
            'idempotency_key' => 'foundation-heartbeat:old',
            'completed_at' => now()->subSeconds(301),
        ]);

        self::assertSame(HealthStatus::Stale, app(QueueHealthService::class)->inspect()->status);

        JobIdempotencyRecord::query()->where('idempotency_key', 'foundation-heartbeat:old')->update([
            'completed_at' => now()->subSeconds(300),
        ]);

        self::assertSame(HealthStatus::Healthy, app(QueueHealthService::class)->inspect()->status);
    }

    public function test_it_reports_an_unconfigured_queue_as_unavailable(): void
    {
        config()->set('queue.default', '');

        $health = app(QueueHealthService::class)->inspect();

        self::assertSame(HealthStatus::Unavailable, $health->status);
    }
}

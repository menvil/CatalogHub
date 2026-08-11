<?php

declare(strict_types=1);

namespace App\Services\Operations;

use App\Models\JobIdempotencyRecord;
use App\Support\Operations\HealthStatus;
use App\Support\Operations\QueueHealth;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Database\DatabaseManager;
use Throwable;

final class QueueHealthService
{
    public function __construct(
        private readonly QueueFactory $queues,
        private readonly DatabaseManager $database,
    ) {}

    public function inspect(): QueueHealth
    {
        $connection = config('queue.default');

        if (! is_string($connection) || $connection === '') {
            return new QueueHealth(HealthStatus::Unavailable, 'No queue connection is configured.', null, 0);
        }

        try {
            $this->queues->connection($connection);
            $failedJobs = $this->recentFailureCount();
            $lastHeartbeatAt = $this->lastHeartbeatAt();
        } catch (Throwable) {
            return new QueueHealth(HealthStatus::Unavailable, 'Queue diagnostics are unavailable.', null, 0);
        }

        if ($failedJobs > 0) {
            return new QueueHealth(
                HealthStatus::Failed,
                'Recent failed queue jobs require inspection.',
                $lastHeartbeatAt,
                $failedJobs,
            );
        }

        if ($lastHeartbeatAt === null || $lastHeartbeatAt->lt(now()->subSeconds($this->staleAfterSeconds()))) {
            return new QueueHealth(
                HealthStatus::Stale,
                'No recent queue heartbeat is available.',
                $lastHeartbeatAt,
                0,
            );
        }

        return new QueueHealth(
            HealthStatus::Healthy,
            'Queue diagnostics and the foundation heartbeat are healthy.',
            $lastHeartbeatAt,
            0,
        );
    }

    private function staleAfterSeconds(): int
    {
        return (int) config('operations.queue_heartbeat_stale_after', 300);
    }

    private function lastHeartbeatAt(): ?CarbonImmutable
    {
        $completedAt = JobIdempotencyRecord::query()
            ->where('idempotency_key', 'like', 'foundation-heartbeat:%')
            ->max('completed_at');

        return $completedAt === null ? null : CarbonImmutable::parse($completedAt);
    }

    private function recentFailureCount(): int
    {
        $driver = config('queue.failed.driver');

        if (! in_array($driver, ['database', 'database-uuids'], true)) {
            return 0;
        }

        $connection = config('queue.failed.database');
        $table = config('queue.failed.table', 'failed_jobs');

        if (! is_string($table) || $table === '') {
            throw new \RuntimeException('Failed-job table is not configured.');
        }

        return $this->database
            ->connection(is_string($connection) ? $connection : null)
            ->table($table)
            ->where('failed_at', '>=', now()->subDay())
            ->count();
    }
}

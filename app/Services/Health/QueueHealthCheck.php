<?php

namespace App\Services\Health;

use Illuminate\Contracts\Queue\Factory as QueueFactory;
use Illuminate\Database\DatabaseManager;
use Throwable;

final class QueueHealthCheck
{
    public function __construct(
        private readonly QueueFactory $queues,
        private readonly DatabaseManager $database,
    ) {}

    public function run(): HealthCheckResult
    {
        $connection = config('queue.default');

        if (! is_string($connection) || $connection === '') {
            return new HealthCheckResult('error', 'No queue connection is configured.');
        }

        try {
            $this->queues->connection($connection);
            $recentFailures = $this->recentFailureCount();
        } catch (Throwable $exception) {
            return new HealthCheckResult('error', 'Queue diagnostics are unavailable.', [
                'connection' => $connection,
                'error_class' => $exception::class,
            ]);
        }

        $isProductionSync = config('app.env') === 'production' && $connection === 'sync';
        $status = $isProductionSync || $recentFailures > 0 ? 'warning' : 'ok';
        $summary = match (true) {
            $isProductionSync => 'The production queue is configured to run synchronously.',
            $recentFailures > 0 => 'Recent failed queue jobs require review.',
            default => 'Queue configuration and failed-job storage are available.',
        };

        return new HealthCheckResult($status, $summary, [
            'connection' => $connection,
            'asynchronous' => $connection !== 'sync',
            'recent_failed_jobs' => $recentFailures,
        ]);
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

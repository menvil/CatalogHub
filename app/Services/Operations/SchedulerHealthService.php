<?php

declare(strict_types=1);

namespace App\Services\Operations;

use App\Models\OperationalHeartbeat;
use App\Support\Operations\HealthStatus;
use App\Support\Operations\SchedulerHealth;
use Carbon\CarbonImmutable;
use Throwable;

final class SchedulerHealthService
{
    public function inspect(): SchedulerHealth
    {
        try {
            $lastRanAt = OperationalHeartbeat::query()
                ->where('name', SchedulerHeartbeatService::NAME)
                ->value('last_ran_at');
        } catch (Throwable) {
            return new SchedulerHealth(HealthStatus::Unavailable, 'Scheduler diagnostics are unavailable.', null);
        }

        $lastRanAt = $lastRanAt === null ? null : CarbonImmutable::parse($lastRanAt);

        if ($lastRanAt === null || $lastRanAt->lt(now()->subSeconds($this->staleAfterSeconds()))) {
            return new SchedulerHealth(HealthStatus::Stale, 'No recent scheduler heartbeat is available.', $lastRanAt);
        }

        return new SchedulerHealth(HealthStatus::Healthy, 'Scheduler heartbeat is healthy.', $lastRanAt);
    }

    private function staleAfterSeconds(): int
    {
        return (int) config('operations.scheduler_heartbeat_stale_after', 300);
    }
}

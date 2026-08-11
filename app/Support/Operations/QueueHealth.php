<?php

declare(strict_types=1);

namespace App\Support\Operations;

use Carbon\CarbonImmutable;

final readonly class QueueHealth
{
    public function __construct(
        public HealthStatus $status,
        public string $summary,
        public ?CarbonImmutable $lastHeartbeatAt,
        public int $recentFailedJobs,
    ) {}
}

<?php

declare(strict_types=1);

namespace App\Support\Operations;

use Carbon\CarbonImmutable;

final readonly class SchedulerHealth
{
    public function __construct(
        public HealthStatus $status,
        public string $summary,
        public ?CarbonImmutable $lastRanAt,
    ) {}
}

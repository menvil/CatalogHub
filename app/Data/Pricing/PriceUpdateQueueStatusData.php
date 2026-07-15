<?php

namespace App\Data\Pricing;

use Carbon\CarbonInterface;

final readonly class PriceUpdateQueueStatusData
{
    public function __construct(
        public int $pendingJobsCount,
        public int $runningJobsCount,
        public int $failedJobsCount,
        public ?CarbonInterface $lastSyncAt,
        public ?string $recentFailedSource,
        public ?string $recentFailureMessage,
        public ?CarbonInterface $recentFailedAt,
    ) {}
}

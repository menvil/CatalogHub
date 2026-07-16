<?php

namespace App\Services\Health;

final readonly class HealthCheckResult
{
    /**
     * @param  array<string, bool|int|string|null>  $details
     */
    public function __construct(
        public string $status,
        public string $summary,
        public array $details = [],
    ) {}
}

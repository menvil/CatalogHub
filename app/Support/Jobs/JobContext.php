<?php

declare(strict_types=1);

namespace App\Support\Jobs;

use App\Support\Http\RequestId;
use Illuminate\Support\Str;
use InvalidArgumentException;

final readonly class JobContext
{
    public function __construct(
        public string $correlationId,
        public ?int $siteId = null,
    ) {
        if (! RequestId::isValid($correlationId)) {
            throw new InvalidArgumentException('Job correlation ID must be a valid request identifier.');
        }

        if ($siteId !== null && $siteId <= 0) {
            throw new InvalidArgumentException('Job site ID must be a positive integer when provided.');
        }
    }

    public static function new(?int $siteId = null): self
    {
        return new self((string) Str::uuid(), $siteId);
    }

    /** @return array<string, int|string> */
    public function logContext(?string $jobId = null): array
    {
        return array_filter([
            'job_id' => $jobId,
            'correlation_id' => $this->correlationId,
            'site_id' => $this->siteId,
        ], static fn (int|string|null $value): bool => $value !== null);
    }
}

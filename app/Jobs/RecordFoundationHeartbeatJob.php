<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Jobs\Middleware\ApplyJobContext;
use App\Services\Operations\FoundationHeartbeatRecorder;
use App\Support\Jobs\JobContext;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use InvalidArgumentException;

final class RecordFoundationHeartbeatJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries;

    public int $timeout;

    public int $uniqueFor;

    public readonly ?int $siteId;

    public readonly string $correlationId;

    public function __construct(
        public readonly string $idempotencyKey,
        ?int $siteId = null,
        ?string $correlationId = null,
    ) {
        if (trim($idempotencyKey) === '') {
            throw new InvalidArgumentException('Job idempotency key must not be empty.');
        }

        $this->siteId = $siteId;
        $context = $correlationId === null
            ? JobContext::new($siteId)
            : new JobContext($correlationId, $siteId);
        $this->correlationId = $context->correlationId;
        $this->tries = (int) config('jobs.fast.tries');
        $this->timeout = (int) config('jobs.fast.timeout');
        $this->uniqueFor = (int) config('jobs.unique_for');
    }

    public function uniqueId(): string
    {
        return $this->idempotencyKey;
    }

    /** @return array<int, ApplyJobContext> */
    public function middleware(): array
    {
        return [new ApplyJobContext(new JobContext($this->correlationId, $this->siteId))];
    }

    public function handle(FoundationHeartbeatRecorder $recorder): void
    {
        $recorder->record($this->idempotencyKey);
    }
}

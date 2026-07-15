<?php

namespace App\Jobs\Pricing\Concerns;

use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Services\Pricing\PriceSourceRetryPolicy;
use Throwable;

trait UsesPriceSourceRetryPolicy
{
    abstract protected function retryPriceSourceId(): int;

    public function tries(): int
    {
        $source = PriceSource::query()->find($this->retryPriceSourceId());

        return $source instanceof PriceSource
            ? app(PriceSourceRetryPolicy::class)->maxAttempts($source)
            : 1;
    }

    /** @return list<int> */
    public function backoff(): array
    {
        $source = PriceSource::query()->find($this->retryPriceSourceId());

        return $source instanceof PriceSource
            ? app(PriceSourceRetryPolicy::class)->backoff($source)
            : [];
    }

    protected function shouldRetryFailure(PriceSource $source, Throwable $exception): bool
    {
        return app(PriceSourceRetryPolicy::class)->shouldRetry(
            $source,
            $this->attempts(),
            $exception,
        );
    }

    /** @return array<string, mixed> */
    protected function retryFailureMetadata(
        PriceSourceSyncLog $log,
        string $stage,
        bool $willRetry,
    ): array {
        $attempts = $log->metadata['retry_attempts'] ?? [];
        $attempts = is_array($attempts) ? $attempts : [];
        $attempts[] = [
            'attempt' => $this->attempts(),
            'stage' => $stage,
            'will_retry' => $willRetry,
            'recorded_at' => now()->toISOString(),
        ];

        return ['stage' => $stage, 'retry_attempts' => $attempts];
    }
}

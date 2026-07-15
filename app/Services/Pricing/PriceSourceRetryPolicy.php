<?php

namespace App\Services\Pricing;

use App\Models\PriceSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use InvalidArgumentException;
use JsonException;
use LogicException;
use Throwable;

final class PriceSourceRetryPolicy
{
    private const DEFAULT_MAX_RETRIES = 3;

    private const DEFAULT_BACKOFF = [60, 300, 900];

    public function shouldRetry(PriceSource $source, int $attempt, ?Throwable $exception = null): bool
    {
        if ($attempt < 1 || $attempt > $this->maxRetries($source)) {
            return false;
        }

        if ($exception === null || $exception instanceof ConnectionException) {
            return true;
        }

        if ($exception instanceof RequestException) {
            $status = $exception->response->status();

            return $status === 408 || $status === 429 || $status >= 500;
        }

        if ($exception instanceof InvalidArgumentException
            || $exception instanceof LogicException
            || $exception instanceof JsonException) {
            return false;
        }

        if (str_contains($exception->getMessage(), 'has no credentials')) {
            return false;
        }

        return true;
    }

    public function maxAttempts(PriceSource $source): int
    {
        return $this->maxRetries($source) + 1;
    }

    /** @return list<int> */
    public function backoff(PriceSource $source): array
    {
        $configured = $source->config_json['retry_delays'] ?? null;

        if (is_array($configured) && $configured !== []) {
            $delays = [];

            foreach ($configured as $delay) {
                if (! is_numeric($delay) || (int) $delay < 0) {
                    $delays = [];
                    break;
                }

                $delays[] = (int) $delay;
            }

            if ($delays !== []) {
                return array_slice($delays, 0, $this->maxRetries($source));
            }
        }

        return array_slice(self::DEFAULT_BACKOFF, 0, $this->maxRetries($source));
    }

    public function maxRetries(PriceSource $source): int
    {
        $configured = $source->config_json['max_retries'] ?? self::DEFAULT_MAX_RETRIES;

        if (! is_numeric($configured)) {
            return self::DEFAULT_MAX_RETRIES;
        }

        return max(0, min(10, (int) $configured));
    }
}

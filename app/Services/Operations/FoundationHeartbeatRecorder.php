<?php

declare(strict_types=1);

namespace App\Services\Operations;

use App\Models\JobIdempotencyRecord;
use InvalidArgumentException;

class FoundationHeartbeatRecorder
{
    public const MAX_IDEMPOTENCY_KEY_LENGTH = 255;

    /**
     * Records one completed operational effect for an idempotency key.
     */
    public function record(string $idempotencyKey): bool
    {
        if (trim($idempotencyKey) === '') {
            throw new InvalidArgumentException('Job idempotency key must not be empty.');
        }

        if (mb_strlen($idempotencyKey) > self::MAX_IDEMPOTENCY_KEY_LENGTH) {
            throw new InvalidArgumentException('Job idempotency key must not exceed 255 characters.');
        }

        return JobIdempotencyRecord::query()->insertOrIgnore([
            'idempotency_key' => $idempotencyKey,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }
}

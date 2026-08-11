<?php

declare(strict_types=1);

namespace App\Services\Operations;

use App\Models\JobIdempotencyRecord;

class FoundationHeartbeatRecorder
{
    /**
     * Records one completed operational effect for an idempotency key.
     */
    public function record(string $idempotencyKey): bool
    {
        return JobIdempotencyRecord::query()->insertOrIgnore([
            'idempotency_key' => $idempotencyKey,
            'completed_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Operations;

use App\Models\OperationalHeartbeat;

final class SchedulerHeartbeatService
{
    public const NAME = 'scheduler';

    public function record(): void
    {
        $now = now();

        OperationalHeartbeat::query()->upsert([
            [
                'name' => self::NAME,
                'last_ran_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ], ['name'], ['last_ran_at', 'updated_at']);
    }
}

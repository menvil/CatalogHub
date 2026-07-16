<?php

namespace App\Services\Sync;

use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SyncLog;
use App\Models\User;
use Throwable;

final class SyncLogWriter
{
    /** @param array<string, mixed> $context */
    public function completed(
        string $operation,
        string $triggeredBy,
        ?User $actor = null,
        ?Site $site = null,
        ?CentralProduct $product = null,
        int $affectedCount = 0,
        array $context = [],
    ): SyncLog {
        $now = now();

        return SyncLog::query()->create([
            'site_id' => $site?->getKey(),
            'central_product_id' => $product?->getKey(),
            'operation' => $operation,
            'status' => 'completed',
            'triggered_by' => $triggeredBy,
            'triggered_by_user_id' => $actor?->getKey(),
            'started_at' => $now,
            'finished_at' => $now,
            'affected_count' => $affectedCount,
            'context_json' => $context,
        ]);
    }

    /** @param array<string, mixed> $context */
    public function failed(
        string $operation,
        string $triggeredBy,
        Throwable|string $error,
        ?User $actor = null,
        ?Site $site = null,
        ?CentralProduct $product = null,
        array $context = [],
    ): SyncLog {
        $now = now();

        return SyncLog::query()->create([
            'site_id' => $site?->getKey(),
            'central_product_id' => $product?->getKey(),
            'operation' => $operation,
            'status' => 'failed',
            'triggered_by' => $triggeredBy,
            'triggered_by_user_id' => $actor?->getKey(),
            'started_at' => $now,
            'finished_at' => $now,
            'affected_count' => 0,
            'error_message' => $error instanceof Throwable ? $error->getMessage() : $error,
            'context_json' => $context,
        ]);
    }
}

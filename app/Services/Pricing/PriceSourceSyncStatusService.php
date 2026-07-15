<?php

namespace App\Services\Pricing;

use App\Enums\PriceSourceStatus;
use App\Enums\PriceSourceSyncStatus;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use LogicException;

final class PriceSourceSyncStatusService
{
    private const COUNTERS = [
        'items_fetched',
        'items_normalized',
        'items_matched',
        'items_updated',
    ];

    public function start(PriceSource $source, PriceSourceSyncLog $log): void
    {
        $this->assertBelongsToSource($source, $log);

        $log->update([
            'status' => PriceSourceSyncStatus::Running,
            'started_at' => $log->started_at ?? now(),
            'finished_at' => null,
            'error_message' => null,
        ]);
    }

    /** @param array<string, int> $counters */
    public function complete(PriceSource $source, PriceSourceSyncLog $log, array $counters = []): void
    {
        $this->assertBelongsToSource($source, $log);
        $counters = $this->validatedCounters($counters);

        DB::transaction(function () use ($source, $log, $counters): void {
            $log->update([
                ...$counters,
                'status' => PriceSourceSyncStatus::Completed,
                'finished_at' => now(),
                'error_message' => null,
            ]);
            $source->update([
                'status' => PriceSourceStatus::Active,
                'last_sync_at' => now(),
            ]);
        });
    }

    /** @param array<string, mixed> $metadata */
    public function fail(
        PriceSource $source,
        PriceSourceSyncLog $log,
        string $message,
        array $metadata = [],
    ): void {
        $this->assertBelongsToSource($source, $log);

        DB::transaction(function () use ($source, $log, $message, $metadata): void {
            $log->update([
                'status' => PriceSourceSyncStatus::Failed,
                'finished_at' => now(),
                'error_message' => $message,
                'metadata' => [...($log->metadata ?? []), ...$metadata],
            ]);
            $source->update(['status' => PriceSourceStatus::Failed]);
        });
    }

    /**
     * @param  array<string, int>  $counters
     * @param  array<string, mixed>  $metadata
     */
    public function partiallyComplete(
        PriceSource $source,
        PriceSourceSyncLog $log,
        array $counters,
        string $message,
        array $metadata = [],
    ): void {
        $this->assertBelongsToSource($source, $log);
        $counters = $this->validatedCounters($counters);

        DB::transaction(function () use ($source, $log, $counters, $message, $metadata): void {
            $log->update([
                ...$counters,
                'status' => PriceSourceSyncStatus::PartiallyCompleted,
                'finished_at' => now(),
                'error_message' => $message,
                'metadata' => [...($log->metadata ?? []), ...$metadata],
            ]);
            $source->update(['status' => PriceSourceStatus::Delayed]);
        });
    }

    private function assertBelongsToSource(PriceSource $source, PriceSourceSyncLog $log): void
    {
        if ((int) $log->price_source_id !== (int) $source->id) {
            throw new LogicException('Price source sync log does not belong to the given source.');
        }
    }

    /**
     * @param  array<string, int>  $counters
     * @return array<string, int>
     */
    private function validatedCounters(array $counters): array
    {
        foreach ($counters as $counter => $value) {
            if (! in_array($counter, self::COUNTERS, true) || $value < 0) {
                throw new InvalidArgumentException("Invalid price source sync counter [{$counter}].");
            }
        }

        return $counters;
    }
}

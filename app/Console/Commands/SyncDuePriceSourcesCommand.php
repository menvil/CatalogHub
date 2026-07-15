<?php

namespace App\Console\Commands;

use App\Services\Pricing\PriceSourceScheduleService;
use App\Services\Pricing\PriceSourceSyncService;
use Illuminate\Console\Command;
use Throwable;

final class SyncDuePriceSourcesCommand extends Command
{
    protected $signature = 'pricing:sync-due-sources';

    protected $description = 'Queue synchronization for all due active price sources';

    public function handle(
        PriceSourceScheduleService $scheduleService,
        PriceSourceSyncService $syncService,
    ): int {
        $sources = $scheduleService->dueSources();
        $queued = 0;

        foreach ($sources as $source) {
            try {
                $syncService->sync($source);
                $queued++;
            } catch (Throwable $exception) {
                $this->error("Failed to queue [{$source->name}]: {$exception->getMessage()}");
            }
        }

        $this->info("Queued {$queued} due price source sync(s).");

        return self::SUCCESS;
    }
}

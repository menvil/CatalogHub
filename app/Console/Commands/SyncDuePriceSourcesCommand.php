<?php

namespace App\Console\Commands;

use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Services\Pricing\PriceSourceScheduleService;
use App\Services\Pricing\PriceSourceSyncService;
use Illuminate\Console\Command;

final class SyncDuePriceSourcesCommand extends Command
{
    protected $signature = 'pricing:sync-due-sources';

    protected $description = 'Queue synchronization for all due active price sources';

    public function handle(
        PriceSourceScheduleService $scheduleService,
        PriceSourceSyncService $syncService,
    ): int {
        $sources = $scheduleService->dueSources();

        $sources->each(fn (PriceSource $source): PriceSourceSyncLog => $syncService->sync($source));

        $this->info("Queued {$sources->count()} due price source sync(s).");

        return self::SUCCESS;
    }
}

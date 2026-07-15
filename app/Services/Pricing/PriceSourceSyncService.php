<?php

namespace App\Services\Pricing;

use App\Jobs\Pricing\FetchExternalOffersJob;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;

final class PriceSourceSyncService
{
    public function __construct(
        private readonly PriceSourceScheduleService $scheduleService,
    ) {}

    public function sync(PriceSource $source): PriceSourceSyncLog
    {
        $log = $source->syncLogs()->create([
            'status' => 'queued',
            'items_fetched' => 0,
            'items_normalized' => 0,
            'items_matched' => 0,
            'items_updated' => 0,
        ]);

        FetchExternalOffersJob::dispatch($source->id, $log->id)->afterCommit();

        return $log;
    }

    public function syncAllDue(): void
    {
        $this->scheduleService
            ->dueSources()
            ->each(fn (PriceSource $source): PriceSourceSyncLog => $this->sync($source));
    }
}

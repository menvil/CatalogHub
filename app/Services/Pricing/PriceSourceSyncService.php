<?php

namespace App\Services\Pricing;

use App\Enums\PriceSourceStatus;
use App\Jobs\Pricing\FetchExternalOffersJob;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use Illuminate\Support\Collection;

final class PriceSourceSyncService
{
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
        $this->dueSources()->each(fn (PriceSource $source): PriceSourceSyncLog => $this->sync($source));
    }

    /** @return Collection<int, PriceSource> */
    private function dueSources(): Collection
    {
        return PriceSource::query()
            ->where('status', PriceSourceStatus::Active->value)
            ->get()
            ->filter(function (PriceSource $source): bool {
                if ($source->update_frequency === null || $source->update_frequency === 'manual') {
                    return false;
                }

                if ($source->last_sync_at === null) {
                    return true;
                }

                $dueAt = match ($source->update_frequency) {
                    'hourly' => $source->last_sync_at->addHour(),
                    'every_6_hours' => $source->last_sync_at->addHours(6),
                    'daily' => $source->last_sync_at->addDay(),
                    'weekly' => $source->last_sync_at->addWeek(),
                    default => null,
                };

                return $dueAt?->isPast() ?? false;
            })
            ->values();
    }
}

<?php

namespace App\Services\Pricing;

use App\Data\Pricing\PriceUpdateQueueStatusData;
use App\Enums\PriceSourceSyncStatus;
use App\Models\PriceSourceSyncLog;
use App\Models\Site;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;

final readonly class PriceUpdateQueueStatusBuilder
{
    public function __construct(private SitePriceSourceSelection $sourceSelection) {}

    public function build(Site $site): PriceUpdateQueueStatusData
    {
        $sourceIds = $this->sourceSelection->enabledSources($site)->select('price_sources.id');
        $logs = PriceSourceSyncLog::query()->whereIn('price_source_id', $sourceIds);
        $latestLog = (clone $logs)->latest('id')->first();
        $recentFailure = (clone $logs)
            ->where('status', PriceSourceSyncStatus::Failed)
            ->latest('id')
            ->first();

        return new PriceUpdateQueueStatusData(
            pendingJobsCount: $this->countByStatus($logs, PriceSourceSyncStatus::Queued),
            runningJobsCount: $this->countByStatus($logs, PriceSourceSyncStatus::Running),
            failedJobsCount: $this->countByStatus($logs, PriceSourceSyncStatus::Failed),
            lastSyncAt: $this->syncTime($latestLog),
            recentFailedSource: $this->failedSourceName($recentFailure),
            recentFailureMessage: $this->stringAttribute($recentFailure, 'error_message'),
            recentFailedAt: $this->syncTime($recentFailure),
        );
    }

    /** @param Builder<PriceSourceSyncLog> $logs */
    private function countByStatus(Builder $logs, PriceSourceSyncStatus $status): int
    {
        return (clone $logs)->where('status', $status)->count();
    }

    private function syncTime(?PriceSourceSyncLog $log): ?CarbonInterface
    {
        if (! $log instanceof PriceSourceSyncLog) {
            return null;
        }

        foreach (['finished_at', 'started_at', 'created_at'] as $attribute) {
            $value = $log->getAttribute($attribute);

            if ($value instanceof CarbonInterface) {
                return $value;
            }
        }

        return null;
    }

    private function failedSourceName(?PriceSourceSyncLog $log): ?string
    {
        if (! $log instanceof PriceSourceSyncLog) {
            return null;
        }

        $name = $log->priceSource()->value('name');

        return is_string($name) ? $name : null;
    }

    private function stringAttribute(?PriceSourceSyncLog $log, string $attribute): ?string
    {
        if (! $log instanceof PriceSourceSyncLog) {
            return null;
        }

        $value = $log->getAttribute($attribute);

        return is_string($value) && $value !== '' ? $value : null;
    }
}

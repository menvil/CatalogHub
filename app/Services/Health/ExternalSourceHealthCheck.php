<?php

namespace App\Services\Health;

use App\Enums\PriceSourceStatus;
use App\Enums\PriceSourceSyncStatus;
use App\Enums\PriceSourceUpdateFrequency;
use App\Models\Imports\ImportSource;
use App\Models\PriceSource;
use Carbon\CarbonInterface;

final class ExternalSourceHealthCheck
{
    /** @var list<string> */
    private const SCHEDULED_IMPORT_TYPES = [
        ImportSource::TYPE_API,
        ImportSource::TYPE_SCRAPER,
        ImportSource::TYPE_MERCHANT_FEED,
    ];

    public function run(): HealthCheckResult
    {
        [$activeImports, $importWarnings, $importErrors] = $this->importSourceCounts();
        [$activePrices, $priceWarnings, $priceErrors] = $this->priceSourceCounts();

        $errors = $importErrors + $priceErrors;
        $warnings = $importWarnings + $priceWarnings;
        $status = $errors > 0 ? 'error' : ($warnings > 0 ? 'warning' : 'ok');
        $summary = match ($status) {
            'error' => 'External sources have repeated failures or an explicit failed state.',
            'warning' => 'External sources include delayed, stale, or never-run integrations.',
            default => 'Enabled external sources have an accepted recent state.',
        };

        return new HealthCheckResult($status, $summary, [
            'active_import_sources' => $activeImports,
            'warning_import_sources' => $importWarnings,
            'error_import_sources' => $importErrors,
            'active_price_sources' => $activePrices,
            'warning_price_sources' => $priceWarnings,
            'error_price_sources' => $priceErrors,
        ]);
    }

    /** @return array{int, int, int} */
    private function importSourceCounts(): array
    {
        $sources = ImportSource::query()->where('status', 'active')->get();
        $warnings = 0;
        $errors = 0;

        foreach ($sources as $source) {
            $recentStatuses = $source->batches()
                ->latest('id')
                ->limit(3)
                ->pluck('status')
                ->all();

            if (count($recentStatuses) === 3 && count(array_unique($recentStatuses)) === 1 && $recentStatuses[0] === 'failed') {
                $errors++;

                continue;
            }

            if (! in_array($source->type, self::SCHEDULED_IMPORT_TYPES, true)) {
                continue;
            }

            $lastSuccess = $source->batches()
                ->where('status', 'completed')
                ->latest('finished_at')
                ->value('finished_at');

            if (! $this->isRecent($lastSuccess, now()->subHours(48))) {
                $warnings++;
            }
        }

        return [$sources->count(), $warnings, $errors];
    }

    /** @return array{int, int, int} */
    private function priceSourceCounts(): array
    {
        $sources = PriceSource::query()
            ->whereIn('status', [
                PriceSourceStatus::Active->value,
                PriceSourceStatus::Delayed->value,
                PriceSourceStatus::Failed->value,
            ])
            ->get();
        $warnings = 0;
        $errors = 0;

        foreach ($sources as $source) {
            if ($source->status === PriceSourceStatus::Failed || $this->hasRepeatedPriceFailures($source)) {
                $errors++;

                continue;
            }

            if ($source->status === PriceSourceStatus::Delayed || $this->isPriceSourceStale($source)) {
                $warnings++;
            }
        }

        return [$sources->count(), $warnings, $errors];
    }

    private function hasRepeatedPriceFailures(PriceSource $source): bool
    {
        $statuses = $source->syncLogs()
            ->latest('id')
            ->limit(3)
            ->pluck('status')
            ->map(static fn (mixed $status): string => $status instanceof PriceSourceSyncStatus ? $status->value : (string) $status)
            ->all();

        return count($statuses) === 3
            && count(array_unique($statuses)) === 1
            && $statuses[0] === PriceSourceSyncStatus::Failed->value;
    }

    private function isPriceSourceStale(PriceSource $source): bool
    {
        $hours = match ($source->update_frequency) {
            PriceSourceUpdateFrequency::Hourly => 2,
            PriceSourceUpdateFrequency::EverySixHours => 12,
            PriceSourceUpdateFrequency::Daily => 48,
            PriceSourceUpdateFrequency::Weekly => 336,
            default => null,
        };

        return $hours !== null && ! $this->isRecent($source->last_sync_at, now()->subHours($hours));
    }

    private function isRecent(mixed $timestamp, CarbonInterface $threshold): bool
    {
        if ($timestamp instanceof CarbonInterface) {
            return $timestamp->greaterThanOrEqualTo($threshold);
        }

        return is_string($timestamp) && $timestamp !== ''
            && now()->parse($timestamp)->greaterThanOrEqualTo($threshold);
    }
}

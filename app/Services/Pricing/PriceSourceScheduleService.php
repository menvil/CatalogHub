<?php

namespace App\Services\Pricing;

use App\Enums\PriceSourceStatus;
use App\Enums\PriceSourceUpdateFrequency;
use App\Models\PriceSource;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

final class PriceSourceScheduleService
{
    /** @return Collection<int, PriceSource> */
    public function dueSources(?CarbonInterface $at = null): Collection
    {
        $at = $at === null ? CarbonImmutable::now() : CarbonImmutable::instance($at);

        return PriceSource::query()
            ->where('status', PriceSourceStatus::Active->value)
            ->get()
            ->filter(fn (PriceSource $source): bool => $this->isDue($source, $at))
            ->values();
    }

    private function isDue(PriceSource $source, CarbonImmutable $at): bool
    {
        $frequency = $source->update_frequency;

        if ($frequency === null || $frequency === PriceSourceUpdateFrequency::Manual) {
            return false;
        }

        if ($source->last_sync_at === null) {
            return true;
        }

        $lastSyncAt = CarbonImmutable::instance($source->last_sync_at);
        $dueAt = match ($frequency) {
            PriceSourceUpdateFrequency::Hourly => $lastSyncAt->addHour(),
            PriceSourceUpdateFrequency::EverySixHours => $lastSyncAt->addHours(6),
            PriceSourceUpdateFrequency::Daily => $lastSyncAt->addDay(),
            PriceSourceUpdateFrequency::Weekly => $lastSyncAt->addWeek(),
        };

        return $dueAt->lessThanOrEqualTo($at);
    }
}

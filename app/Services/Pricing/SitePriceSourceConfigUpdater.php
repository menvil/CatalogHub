<?php

namespace App\Services\Pricing;

use App\Models\PriceSource;
use App\Models\Site;
use App\Models\SitePriceSource;

final class SitePriceSourceConfigUpdater
{
    /** @param list<array<string, mixed>> $rows */
    public function update(Site $site, array $rows): void
    {
        $marketSourceIds = PriceSource::query()
            ->where('market_id', $site->market_id)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->flip();

        foreach ($rows as $row) {
            $sourceId = filter_var($row['price_source_id'] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1],
            ]);

            if ($sourceId === false || ! $marketSourceIds->has($sourceId)) {
                continue;
            }

            $pivot = SitePriceSource::query()
                ->where('site_id', $site->id)
                ->where('price_source_id', $sourceId)
                ->first();

            if (! $pivot instanceof SitePriceSource) {
                continue;
            }

            $priority = $this->integer($row['priority'] ?? null, 0, 65535);
            $site->priceSources()->updateExistingPivot($sourceId, [
                'priority' => $priority,
                'config_json' => [
                    'freshness' => [
                        'fresh_hours' => $this->hours($row['fresh_hours'] ?? null),
                        'stale_hours' => $this->hours($row['stale_hours'] ?? null),
                        'expired_hours' => $this->hours($row['expired_hours'] ?? null),
                    ],
                    'allow_default_market_currency' => ($row['allow_default_market_currency'] ?? true) === true,
                    'include_out_of_stock' => ($row['include_out_of_stock'] ?? true) === true,
                ],
            ]);
        }
    }

    private function hours(mixed $value): int
    {
        return $this->integer($value, 0) ?? 0;
    }

    private function integer(mixed $value, int $minimum, ?int $maximum = null): ?int
    {
        if (! is_numeric($value) || (float) $value !== floor((float) $value)) {
            return null;
        }

        $integer = (int) $value;

        return $integer >= $minimum && ($maximum === null || $integer <= $maximum)
            ? $integer
            : null;
    }
}

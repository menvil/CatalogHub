<?php

namespace App\Services\Pricing;

use App\Data\Pricing\SitePriceSourceConfigData;
use App\Enums\OfferAvailability;
use App\Enums\PriceSourceStatus;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\Site;
use App\Models\SitePriceSource;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

final class SitePriceSourceConfigResolver
{
    /** @var array<string, SitePriceSourceConfigData> */
    private array $cache = [];

    public function __construct(private readonly SitePriceSourceSelection $selection) {}

    public function resolve(Site $site, PriceSource $source): SitePriceSourceConfigData
    {
        $cacheKey = $site->getKey().':'.$source->getKey();

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $defaults = $this->defaultThresholds();
        $pivot = SitePriceSource::query()
            ->where('site_id', $site->id)
            ->where('price_source_id', $source->id)
            ->first();
        $config = $pivot instanceof SitePriceSource ? ($pivot->config_json ?? []) : [];
        $thresholds = [
            'fresh' => $this->integer(data_get($config, 'freshness.fresh_hours'), $defaults['fresh']),
            'stale' => $this->integer(data_get($config, 'freshness.stale_hours'), $defaults['stale']),
            'expired' => $this->integer(data_get($config, 'freshness.expired_hours'), $defaults['expired']),
        ];

        if (! $this->validThresholds($thresholds)) {
            $thresholds = $defaults;
        }

        $sameMarket = (int) $source->market_id === (int) $site->market_id;
        $enabledBySelection = $pivot instanceof SitePriceSource
            ? $pivot->enabled
            : ! $this->selection->hasExplicitSelection($site);

        return $this->cache[$cacheKey] = new SitePriceSourceConfigData(
            enabled: $sameMarket && $source->status === PriceSourceStatus::Active && $enabledBySelection,
            priority: $pivot instanceof SitePriceSource ? $pivot->priority : null,
            freshHours: $thresholds['fresh'],
            staleHours: $thresholds['stale'],
            expiredHours: $thresholds['expired'],
            allowDefaultMarketCurrency: $this->boolean(
                data_get($config, 'allow_default_market_currency'),
                true,
            ),
            includeOutOfStock: $this->boolean(data_get($config, 'include_out_of_stock'), true),
        );
    }

    /** @param Builder<MarketOffer> $query
     * @return Builder<MarketOffer>
     */
    public function applySummaryPolicy(Builder $query, Site $site): Builder
    {
        $excludeOutOfStockFor = $this->selection->enabledSources($site)
            ->get()
            ->filter(fn (PriceSource $source): bool => ! $this->resolve($site, $source)->includeOutOfStock)
            ->modelKeys();

        if ($excludeOutOfStockFor === []) {
            return $query;
        }

        return $query->where(function (Builder $policy) use ($excludeOutOfStockFor): void {
            $policy
                ->whereNotIn('market_offers.price_source_id', $excludeOutOfStockFor)
                ->orWhere('market_offers.availability', '!=', OfferAvailability::OutOfStock);
        });
    }

    /** @return array{fresh: int, stale: int, expired: int} */
    public function defaultThresholds(): array
    {
        $thresholds = [
            'fresh' => (int) config('pricing.freshness.fresh_hours', 6),
            'stale' => (int) config('pricing.freshness.stale_hours', 24),
            'expired' => (int) config('pricing.freshness.expired_hours', 48),
        ];

        if (! $this->validThresholds($thresholds)) {
            throw new InvalidArgumentException('Price freshness thresholds must be non-negative and ascending.');
        }

        return $thresholds;
    }

    /** @param array{fresh: int, stale: int, expired: int} $thresholds */
    private function validThresholds(array $thresholds): bool
    {
        return $thresholds['fresh'] >= 0
            && $thresholds['fresh'] <= $thresholds['stale']
            && $thresholds['stale'] <= $thresholds['expired'];
    }

    private function integer(mixed $value, int $default): int
    {
        return is_int($value) || (is_string($value) && ctype_digit($value))
            ? (int) $value
            : $default;
    }

    private function boolean(mixed $value, bool $default): bool
    {
        return is_bool($value) ? $value : $default;
    }
}

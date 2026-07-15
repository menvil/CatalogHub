<?php

namespace App\Services\Pricing;

use App\Enums\PriceFreshnessStatus;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use Carbon\CarbonImmutable;
use DateTimeInterface;

final class PriceFreshnessCalculator
{
    public function __construct(private readonly SitePriceSourceConfigResolver $sourceConfig) {}

    public function calculate(
        MarketOffer|SiteSearchDocument|DateTimeInterface|null $subject,
        ?DateTimeInterface $now = null,
        ?Site $site = null,
    ): PriceFreshnessStatus {
        $updatedAt = $this->timestamp($subject);

        if ($updatedAt === null) {
            return PriceFreshnessStatus::Unknown;
        }

        $now = $now === null ? CarbonImmutable::now() : CarbonImmutable::instance($now);
        $thresholds = $this->thresholds($subject, $site);
        $ageInSeconds = max(0, $now->getTimestamp() - $updatedAt->getTimestamp());

        return $this->statusForAge($ageInSeconds, $thresholds);
    }

    /** @return array{fresh: CarbonImmutable, stale: CarbonImmutable, expired: CarbonImmutable} */
    public function defaultCutoffs(?DateTimeInterface $now = null): array
    {
        $now = $now === null ? CarbonImmutable::now() : CarbonImmutable::instance($now);
        $thresholds = $this->sourceConfig->defaultThresholds();

        return [
            'fresh' => $now->subHours($thresholds['fresh']),
            'stale' => $now->subHours($thresholds['stale']),
            'expired' => $now->subHours($thresholds['expired']),
        ];
    }

    private function timestamp(
        MarketOffer|SiteSearchDocument|DateTimeInterface|null $subject,
    ): ?CarbonImmutable {
        if ($subject instanceof DateTimeInterface) {
            return CarbonImmutable::instance($subject);
        }

        $timestamp = match (true) {
            $subject instanceof MarketOffer => $subject->getAttribute('last_checked_at'),
            $subject instanceof SiteSearchDocument => $subject->getAttribute('last_price_update_at'),
            default => null,
        };

        return $timestamp instanceof DateTimeInterface
            ? CarbonImmutable::instance($timestamp)
            : null;
    }

    /** @return array{fresh: int, stale: int, expired: int} */
    private function thresholds(
        MarketOffer|SiteSearchDocument|DateTimeInterface|null $subject,
        ?Site $site,
    ): array {
        if ($subject instanceof MarketOffer && $site instanceof Site) {
            $source = $subject->priceSource;

            if ($source instanceof PriceSource) {
                $config = $this->sourceConfig->resolve($site, $source);

                return [
                    'fresh' => $config->freshHours,
                    'stale' => $config->staleHours,
                    'expired' => $config->expiredHours,
                ];
            }
        }

        return $this->sourceConfig->defaultThresholds();
    }

    /** @param array{fresh: int, stale: int, expired: int} $thresholds */
    private function statusForAge(int $ageInSeconds, array $thresholds): PriceFreshnessStatus
    {
        return match (true) {
            $ageInSeconds >= $thresholds['expired'] * 3600 => PriceFreshnessStatus::Expired,
            $ageInSeconds <= $thresholds['fresh'] * 3600 => PriceFreshnessStatus::Fresh,
            $ageInSeconds <= $thresholds['stale'] * 3600 => PriceFreshnessStatus::Stale,
            default => PriceFreshnessStatus::Stale,
        };
    }
}

<?php

namespace App\Services\Pricing;

use App\Enums\PriceFreshnessStatus;
use App\Models\MarketOffer;
use App\Models\SiteSearchDocument;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final class PriceFreshnessCalculator
{
    public function calculate(
        MarketOffer|SiteSearchDocument|DateTimeInterface|null $subject,
        ?DateTimeInterface $now = null,
    ): PriceFreshnessStatus {
        $updatedAt = $this->timestamp($subject);

        if ($updatedAt === null) {
            return PriceFreshnessStatus::Unknown;
        }

        $now = $now === null ? CarbonImmutable::now() : CarbonImmutable::instance($now);
        $thresholds = $this->thresholds();
        $ageInSeconds = max(0, $now->getTimestamp() - $updatedAt->getTimestamp());

        if ($ageInSeconds >= $thresholds['expired'] * 3600) {
            return PriceFreshnessStatus::Expired;
        }

        if ($ageInSeconds <= $thresholds['fresh'] * 3600) {
            return PriceFreshnessStatus::Fresh;
        }

        return PriceFreshnessStatus::Stale;
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
    private function thresholds(): array
    {
        $thresholds = [
            'fresh' => (int) config('pricing.freshness.fresh_hours', 6),
            'stale' => (int) config('pricing.freshness.stale_hours', 24),
            'expired' => (int) config('pricing.freshness.expired_hours', 48),
        ];

        if ($thresholds['fresh'] < 0
            || $thresholds['fresh'] > $thresholds['stale']
            || $thresholds['stale'] > $thresholds['expired']) {
            throw new InvalidArgumentException('Price freshness thresholds must be non-negative and ascending.');
        }

        return $thresholds;
    }
}

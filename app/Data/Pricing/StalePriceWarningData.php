<?php

namespace App\Data\Pricing;

use Carbon\CarbonInterface;

final readonly class StalePriceWarningData
{
    public function __construct(
        public int $staleOffersCount,
        public int $expiredOffersCount,
        public int $affectedProductsCount,
        public ?CarbonInterface $lastSuccessfulUpdateAt,
    ) {}

    public function hasWarning(): bool
    {
        return $this->staleOffersCount > 0 || $this->expiredOffersCount > 0;
    }
}

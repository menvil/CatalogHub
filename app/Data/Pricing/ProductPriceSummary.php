<?php

namespace App\Data\Pricing;

use Carbon\CarbonImmutable;

final readonly class ProductPriceSummary
{
    public function __construct(
        public ?string $minPrice,
        public ?string $maxPrice,
        public int $offersCount,
        public bool $inStock,
        public ?CarbonImmutable $lastPriceUpdateAt,
    ) {}
}

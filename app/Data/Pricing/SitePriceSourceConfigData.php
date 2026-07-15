<?php

namespace App\Data\Pricing;

final readonly class SitePriceSourceConfigData
{
    public function __construct(
        public bool $enabled,
        public ?int $priority,
        public int $freshHours,
        public int $staleHours,
        public int $expiredHours,
        public bool $allowDefaultMarketCurrency,
        public bool $includeOutOfStock,
    ) {}
}

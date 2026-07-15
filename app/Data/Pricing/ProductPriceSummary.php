<?php

namespace App\Data\Pricing;

final readonly class ProductPriceSummary
{
    public function __construct(
        public ?string $minPrice,
    ) {}
}

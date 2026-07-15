<?php

namespace App\Data\Pricing;

final readonly class ExternalPriceWidgetData
{
    public function __construct(
        public string $provider,
        public string $sourceUrl,
    ) {}
}

<?php

namespace App\Contracts\Pricing;

use App\Data\Pricing\ExternalPriceOfferData;
use App\Data\Pricing\PriceSourceFetchResult;
use App\Models\PriceSource;

interface PriceSourceAdapterInterface
{
    public function supports(PriceSource $source): bool;

    public function fetchOffers(PriceSource $source): PriceSourceFetchResult;

    /** @param array<string, mixed> $rawPayload */
    public function normalizeOffer(PriceSource $source, array $rawPayload): ExternalPriceOfferData;
}

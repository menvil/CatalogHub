<?php

namespace App\Pricing\Adapters;

use App\Contracts\Pricing\PriceSourceAdapterInterface;
use App\Data\Pricing\ExternalPriceOfferData;
use App\Data\Pricing\PriceSourceFetchResult;
use App\Enums\PriceSourceType;
use App\Models\PriceSource;
use App\Pricing\Adapters\Concerns\NormalizesExternalPriceOffers;
use InvalidArgumentException;

final class ManualOfferAdapter implements PriceSourceAdapterInterface
{
    use NormalizesExternalPriceOffers;

    public function supports(PriceSource $source): bool
    {
        return $source->type === PriceSourceType::Manual;
    }

    public function fetchOffers(PriceSource $source): PriceSourceFetchResult
    {
        $offers = $source->config_json['offers'] ?? [];

        if (! is_array($offers)) {
            throw new InvalidArgumentException('Manual price source offers must be an array.');
        }

        foreach ($offers as $offer) {
            if (! is_array($offer)) {
                throw new InvalidArgumentException('Every manual price source offer must be an object.');
            }
        }

        /** @var list<array<string, mixed>> $offers */
        return PriceSourceFetchResult::fromOffers($offers);
    }

    public function normalizeOffer(PriceSource $source, array $rawPayload): ExternalPriceOfferData
    {
        return $this->normalizedOffer($source, $rawPayload);
    }
}

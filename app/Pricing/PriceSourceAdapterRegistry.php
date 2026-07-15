<?php

namespace App\Pricing;

use App\Contracts\Pricing\PriceSourceAdapterInterface;
use App\Models\PriceSource;
use App\Pricing\Adapters\CsvFeedPriceAdapter;
use App\Pricing\Adapters\GenericApiPriceAdapter;
use App\Pricing\Adapters\ManualOfferAdapter;
use InvalidArgumentException;

final class PriceSourceAdapterRegistry
{
    /** @var list<PriceSourceAdapterInterface> */
    private array $adapters;

    public function __construct(
        ManualOfferAdapter $manualOfferAdapter,
        CsvFeedPriceAdapter $csvFeedPriceAdapter,
        GenericApiPriceAdapter $genericApiPriceAdapter,
    ) {
        $this->adapters = [$manualOfferAdapter, $csvFeedPriceAdapter, $genericApiPriceAdapter];
    }

    public function for(PriceSource $source): PriceSourceAdapterInterface
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($source)) {
                return $adapter;
            }
        }

        throw new InvalidArgumentException("No price source adapter supports source [{$source->getKey()}].");
    }
}

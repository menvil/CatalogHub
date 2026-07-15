<?php

namespace App\Data\Pricing;

final readonly class PriceSourceFetchResult
{
    /**
     * @param  list<array<array-key, mixed>>  $offers
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public array $offers,
        public array $metadata = [],
    ) {}

    /** @param list<array<array-key, mixed>> $offers */
    public static function fromOffers(array $offers, array $metadata = []): self
    {
        return new self($offers, $metadata);
    }

    public static function empty(): self
    {
        return new self([]);
    }
}

<?php

namespace App\Data\Pricing;

final readonly class PriceSourceFetchResult
{
    /**
     * @param  list<array<string, mixed>>  $offers
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public array $offers,
        public array $metadata = [],
    ) {}

    /** @param list<array<string, mixed>> $offers */
    public static function fromOffers(array $offers, array $metadata = []): self
    {
        return new self(array_values($offers), $metadata);
    }

    public static function empty(): self
    {
        return new self([]);
    }
}

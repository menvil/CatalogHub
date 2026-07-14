<?php

namespace App\Data\Facets;

final readonly class AppliedFacetFilter
{
    /** @param list<string> $queryKeys */
    public function __construct(
        public string $code,
        public string $label,
        public mixed $value,
        public array $queryKeys = [],
    ) {}
}

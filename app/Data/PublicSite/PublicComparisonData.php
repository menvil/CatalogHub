<?php

namespace App\Data\PublicSite;

final readonly class PublicComparisonData
{
    /** @param list<string> $slugs */
    public function __construct(
        public array $slugs,
    ) {}
}

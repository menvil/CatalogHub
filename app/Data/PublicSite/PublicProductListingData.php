<?php

namespace App\Data\PublicSite;

use App\Data\Facets\FacetFilterSet;

final readonly class PublicProductListingData
{
    public function __construct(
        public FacetFilterSet $filters,
        public int $perPage,
    ) {}
}

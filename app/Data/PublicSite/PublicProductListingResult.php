<?php

namespace App\Data\PublicSite;

use App\Models\CentralCatalog\CentralCategory;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

final readonly class PublicProductListingResult
{
    /**
     * @param  LengthAwarePaginator<int, SiteSearchDocument>  $documents
     * @param  Collection<int, SiteProductProjection>  $projections
     */
    public function __construct(
        public SiteCategoryProjection $category,
        public CentralCategory $centralCategory,
        public LengthAwarePaginator $documents,
        public Collection $projections,
    ) {}
}

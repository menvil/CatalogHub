<?php

namespace App\Data\PublicSite;

use App\Models\MarketOffer;
use App\Models\Review;
use App\Models\SiteProductProjection;
use Illuminate\Support\Collection;

final readonly class PublicProductPageData
{
    /**
     * @param  Collection<int, Review>  $reviews
     * @param  Collection<int, MarketOffer>  $offers
     */
    public function __construct(
        public SiteProductProjection $projection,
        public bool $reviewsEnabled,
        public bool $leadsEnabled,
        public Collection $reviews,
        public Collection $offers,
    ) {}
}

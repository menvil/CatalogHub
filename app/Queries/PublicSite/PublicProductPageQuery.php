<?php

namespace App\Queries\PublicSite;

use App\Data\PublicSite\PublicProductPageData;
use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\Review;
use App\Models\Site;
use App\Models\SiteProductProjection;
use App\Queries\Pricing\ValidMarketOfferQuery;

final readonly class PublicProductPageQuery
{
    public function __construct(
        private ValidMarketOfferQuery $validOffers,
    ) {}

    public function get(Site $site, string $locale, string $slug): PublicProductPageData
    {
        $projection = SiteProductProjection::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('status', ProjectionStatus::Active)
            ->firstOrFail();
        $enabledFeatures = $site->features()
            ->where('is_enabled', true)
            ->pluck('feature_key');
        $reviewsEnabled = $enabledFeatures->contains('reviews');
        $reviews = $reviewsEnabled
            ? Review::query()
                ->visiblePublicly()
                ->forSite($site)
                ->where('central_product_id', $projection->central_product_id)
                ->latest('approved_at')
                ->latest('id')
                ->limit(50)
                ->get()
            : collect();
        $offers = $this->validOffers->forProduct($site, (int) $projection->central_product_id)
            ->with(['merchant.logoMediaAsset', 'priceSource'])
            ->orderBy('price')
            ->orderBy('id')
            ->get();

        return new PublicProductPageData(
            projection: $projection,
            reviewsEnabled: $reviewsEnabled,
            leadsEnabled: $enabledFeatures->contains('leads'),
            reviews: $reviews,
            offers: $offers,
        );
    }
}

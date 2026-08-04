<?php

namespace App\Services\Pricing;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\MarketMerchant;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use App\Queries\Pricing\ValidMarketOfferQuery;
use Illuminate\Database\Eloquent\Collection;

final readonly class MerchantFilterOptionsBuilder
{
    public function __construct(
        private ValidMarketOfferQuery $validOffers,
    ) {}

    /** @return Collection<int, MarketMerchant> */
    public function build(Site $site, CentralCategory $category): Collection
    {
        $documentProductIds = SiteSearchDocument::query()
            ->select('document_id')
            ->where('site_id', $site->id)
            ->where('document_type', 'product')
            ->where('status', ProjectionStatus::Active)
            ->where('filter_values_json->category_id', $category->id);
        $merchantIds = $this->validOffers->forSite($site)
            ->select('market_merchant_id')
            ->whereIn('central_product_id', $documentProductIds)
            ->distinct();

        return MarketMerchant::query()
            ->select(['id', 'market_id', 'name', 'slug'])
            ->where('market_id', $site->market_id)
            ->whereIn('id', $merchantIds)
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }
}

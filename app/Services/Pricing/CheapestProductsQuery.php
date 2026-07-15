<?php

namespace App\Services\Pricing;

use App\Enums\OfferAvailability;
use App\Enums\PriceFreshnessStatus;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use Illuminate\Database\Eloquent\Builder;

final readonly class CheapestProductsQuery
{
    public function __construct(
        private ValidMarketOfferQuery $validOffers,
        private SitePriceSourceConfigResolver $sourceConfig,
    ) {}

    /** @return Builder<SiteSearchDocument> */
    public function forSite(
        Site $site,
        ?int $categoryId = null,
        ?int $brandId = null,
        ?int $merchantId = null,
        ?PriceFreshnessStatus $freshness = null,
        bool $inStockOnly = false,
    ): Builder {
        $bestMerchant = $this->validOffers->forSite($site)
            ->join('market_merchants as best_merchants', 'best_merchants.id', '=', 'market_offers.market_merchant_id')
            ->whereColumn('market_offers.central_product_id', 'site_search_documents.document_id')
            ->where('market_offers.availability', OfferAvailability::InStock)
            ->orderByRaw('(market_offers.price + COALESCE(market_offers.delivery_price, 0)) asc')
            ->orderBy('market_offers.id')
            ->select('best_merchants.name')
            ->limit(1);
        $query = SiteSearchDocument::query()
            ->join('site_products', function ($join) use ($site): void {
                $join
                    ->on('site_products.central_product_id', '=', 'site_search_documents.document_id')
                    ->where('site_products.site_id', $site->id)
                    ->where('site_products.visibility', 'visible');
            })
            ->join('central_products', 'central_products.id', '=', 'site_search_documents.document_id')
            ->leftJoin('central_categories', 'central_categories.id', '=', 'central_products.central_category_id')
            ->leftJoin('central_brands', 'central_brands.id', '=', 'central_products.central_brand_id')
            ->where('site_search_documents.site_id', $site->id)
            ->where('site_search_documents.locale', $site->default_locale)
            ->where('site_search_documents.document_type', 'product')
            ->whereNotNull('site_search_documents.min_price')
            ->select([
                'site_search_documents.*',
                'central_products.name as product_name',
                'central_categories.name as category_name',
                'central_brands.name as brand_name',
            ])
            ->selectSub($bestMerchant, 'best_merchant')
            ->orderBy('site_search_documents.min_price')
            ->orderBy('site_search_documents.id');

        if ($categoryId !== null) {
            $query->where('central_products.central_category_id', $categoryId);
        }
        if ($brandId !== null) {
            $query->where('central_products.central_brand_id', $brandId);
        }
        if ($inStockOnly) {
            $query->where('site_search_documents.in_stock', true);
        }
        if ($merchantId !== null) {
            $merchantOffers = $this->validOffers->forSite($site)
                ->whereColumn('market_offers.central_product_id', 'site_search_documents.document_id')
                ->where('market_offers.market_merchant_id', $merchantId)
                ->selectRaw('1');
            $query->whereExists($merchantOffers->toBase());
        }

        $this->applyFreshness($query, $freshness);

        return $query;
    }

    /** @param Builder<SiteSearchDocument> $query */
    private function applyFreshness(Builder $query, ?PriceFreshnessStatus $freshness): void
    {
        if ($freshness === null) {
            return;
        }

        $thresholds = $this->sourceConfig->defaultThresholds();
        $freshCutoff = now()->subHours($thresholds['fresh']);
        $expiredCutoff = now()->subHours($thresholds['expired']);

        match ($freshness) {
            PriceFreshnessStatus::Fresh => $query->where('site_search_documents.last_price_update_at', '>=', $freshCutoff),
            PriceFreshnessStatus::Stale => $query
                ->where('site_search_documents.last_price_update_at', '<', $freshCutoff)
                ->where('site_search_documents.last_price_update_at', '>', $expiredCutoff),
            PriceFreshnessStatus::Expired => $query->where('site_search_documents.last_price_update_at', '<=', $expiredCutoff),
            PriceFreshnessStatus::Unknown => $query->whereNull('site_search_documents.last_price_update_at'),
        };
    }
}

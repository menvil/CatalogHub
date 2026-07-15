<?php

namespace App\Services\Pricing;

use App\Data\Pricing\OfferCoverageDashboardData;
use App\Models\MarketOffer;
use App\Models\Site;
use Illuminate\Support\Facades\DB;

final readonly class OfferCoverageDashboardBuilder
{
    public function __construct(
        private ValidMarketOfferQuery $validOffers,
        private StalePriceWarningBuilder $stalePrices,
    ) {}

    public function build(Site $site): OfferCoverageDashboardData
    {
        $overall = DB::table('site_products')
            ->leftJoin('site_search_documents as price_documents', function ($join) use ($site): void {
                $join
                    ->on('price_documents.site_id', '=', 'site_products.site_id')
                    ->on('price_documents.document_id', '=', 'site_products.central_product_id')
                    ->where('price_documents.locale', $site->default_locale)
                    ->where('price_documents.document_type', 'product');
            })
            ->where('site_products.site_id', $site->id)
            ->where('site_products.visibility', 'visible')
            ->selectRaw('COUNT(site_products.id) as total')
            ->selectRaw('SUM(CASE WHEN price_documents.offers_count > 0 THEN 1 ELSE 0 END) as covered')
            ->first();
        $total = (int) ($overall->total ?? 0);
        $covered = (int) ($overall->covered ?? 0);
        $categories = DB::table('site_products')
            ->join('central_products', 'central_products.id', '=', 'site_products.central_product_id')
            ->leftJoin('central_categories', 'central_categories.id', '=', 'central_products.central_category_id')
            ->leftJoin('site_search_documents as price_documents', function ($join) use ($site): void {
                $join
                    ->on('price_documents.site_id', '=', 'site_products.site_id')
                    ->on('price_documents.document_id', '=', 'site_products.central_product_id')
                    ->where('price_documents.locale', $site->default_locale)
                    ->where('price_documents.document_type', 'product');
            })
            ->where('site_products.site_id', $site->id)
            ->where('site_products.visibility', 'visible')
            ->groupBy('central_categories.id', 'central_categories.name')
            ->orderBy('central_categories.name')
            ->selectRaw("COALESCE(central_categories.name, 'Uncategorised') as name")
            ->selectRaw('COUNT(site_products.id) as total')
            ->selectRaw('SUM(CASE WHEN price_documents.offers_count > 0 THEN 1 ELSE 0 END) as covered')
            ->get()
            ->map(function (object $row): array {
                $categoryTotal = (int) $row->total;
                $categoryCovered = (int) $row->covered;

                return [
                    'name' => (string) $row->name,
                    'total' => $categoryTotal,
                    'covered' => $categoryCovered,
                    'percent' => $this->percent($categoryCovered, $categoryTotal),
                ];
            })
            ->all();
        $sources = $this->validOffers->forSite($site)
            ->join('site_products', function ($join) use ($site): void {
                $join
                    ->on('site_products.central_product_id', '=', 'market_offers.central_product_id')
                    ->where('site_products.site_id', $site->id)
                    ->where('site_products.visibility', 'visible');
            })
            ->join('price_sources', 'price_sources.id', '=', 'market_offers.price_source_id')
            ->groupBy('price_sources.id', 'price_sources.name')
            ->orderBy('price_sources.name')
            ->selectRaw('price_sources.name as name')
            ->selectRaw('COUNT(DISTINCT market_offers.central_product_id) as covered')
            ->get()
            ->map(function (MarketOffer $row) use ($total): array {
                $coveredBySource = (int) $row->getAttribute('covered');

                return [
                    'name' => (string) $row->getAttribute('name'),
                    'covered' => $coveredBySource,
                    'percent' => $this->percent($coveredBySource, $total),
                ];
            })
            ->all();
        $stale = $this->stalePrices->build($site);

        return new OfferCoverageDashboardData(
            totalVisibleProducts: $total,
            productsWithOffers: $covered,
            productsWithoutOffers: max(0, $total - $covered),
            coveragePercent: $this->percent($covered, $total),
            categoryCoverage: $categories,
            sourceCoverage: $sources,
            staleOffersCount: $stale->staleOffersCount,
            expiredOffersCount: $stale->expiredOffersCount,
        );
    }

    private function percent(int $covered, int $total): float
    {
        return $total === 0 ? 0.0 : round(($covered / $total) * 100, 2);
    }
}

<?php

namespace App\Queries\Pricing;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use App\Models\MarketOffer;
use App\Models\Site;
use App\Models\SiteProduct;

final readonly class OfferCoverageQuery implements RawSqlPersistenceBoundary
{
    public function __construct(private ValidMarketOfferQuery $validOffers) {}

    /** @return array{total: int, covered: int} */
    public function overall(Site $site): array
    {
        $row = SiteProduct::query()
            ->leftJoin('site_search_documents as price_documents', function ($join) use ($site): void {
                $join
                    ->on('price_documents.site_id', '=', 'site_products.site_id')
                    ->on('price_documents.document_id', '=', 'site_products.central_product_id')
                    ->where('price_documents.locale', $site->default_locale)
                    ->where('price_documents.document_type', 'product');
            })
            ->where('site_products.site_id', $site->id)
            ->where('site_products.visibility', 'visible')
            ->selectRaw('COUNT(site_products.id) as total, SUM(CASE WHEN price_documents.offers_count > 0 THEN 1 ELSE 0 END) as covered')
            ->first();

        return [
            'total' => (int) $row?->getAttribute('total'),
            'covered' => (int) $row?->getAttribute('covered'),
        ];
    }

    /** @return list<array{name: string, total: int, covered: int}> */
    public function byCategory(Site $site): array
    {
        return SiteProduct::query()
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
            ->select('central_categories.name as name')
            ->selectRaw('COUNT(site_products.id) as total, SUM(CASE WHEN price_documents.offers_count > 0 THEN 1 ELSE 0 END) as covered')
            ->get()
            ->map(static fn (SiteProduct $row): array => [
                'name' => $row->getAttribute('name') === null
                    ? 'Uncategorised'
                    : (string) $row->getAttribute('name'),
                'total' => (int) $row->getAttribute('total'),
                'covered' => (int) $row->getAttribute('covered'),
            ])
            ->all();
    }

    /** @return list<array{name: string, covered: int}> */
    public function bySource(Site $site): array
    {
        return $this->validOffers->forSite($site)
            ->join('site_products', function ($join) use ($site): void {
                $join
                    ->on('site_products.central_product_id', '=', 'market_offers.central_product_id')
                    ->where('site_products.site_id', $site->id)
                    ->where('site_products.visibility', 'visible');
            })
            ->join('price_sources', 'price_sources.id', '=', 'market_offers.price_source_id')
            ->groupBy('price_sources.id', 'price_sources.name')
            ->orderBy('price_sources.name')
            ->select('price_sources.name as name')
            ->selectRaw('COUNT(DISTINCT market_offers.central_product_id) as covered')
            ->get()
            ->map(static fn (MarketOffer $row): array => [
                'name' => (string) $row->getAttribute('name'),
                'covered' => (int) $row->getAttribute('covered'),
            ])
            ->all();
    }
}

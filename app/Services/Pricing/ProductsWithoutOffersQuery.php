<?php

namespace App\Services\Pricing;

use App\Models\Site;
use App\Models\SiteProduct;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;

final class ProductsWithoutOffersQuery
{
    /** @return Builder<SiteProduct> */
    public function forSite(Site $site, ?int $categoryId = null, ?int $brandId = null): Builder
    {
        $query = SiteProduct::query()
            ->join('central_products', 'central_products.id', '=', 'site_products.central_product_id')
            ->leftJoin('site_search_documents as price_documents', function (JoinClause $join) use ($site): void {
                $join
                    ->on('price_documents.site_id', '=', 'site_products.site_id')
                    ->on('price_documents.document_id', '=', 'site_products.central_product_id')
                    ->where('price_documents.locale', $site->default_locale)
                    ->where('price_documents.document_type', 'product');
            })
            ->leftJoin('site_product_projections as product_projections', function (JoinClause $join) use ($site): void {
                $join
                    ->on('product_projections.site_id', '=', 'site_products.site_id')
                    ->on('product_projections.central_product_id', '=', 'site_products.central_product_id')
                    ->where('product_projections.locale', $site->default_locale);
            })
            ->where('site_products.site_id', $site->id)
            ->where('site_products.visibility', 'visible')
            ->where(function ($prices): void {
                $prices
                    ->whereNull('price_documents.id')
                    ->orWhere('price_documents.offers_count', 0);
            })
            ->select([
                'site_products.*',
                'product_projections.id as projection_id',
                'product_projections.status as projection_status',
                'product_projections.built_at as projection_built_at',
            ])
            ->with(['product.category', 'product.brand'])
            ->orderBy('central_products.name')
            ->orderBy('site_products.id');

        if ($categoryId !== null) {
            $query->where('central_products.central_category_id', $categoryId);
        }

        if ($brandId !== null) {
            $query->where('central_products.central_brand_id', $brandId);
        }

        return $query;
    }
}

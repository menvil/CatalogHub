<?php

namespace App\Http\Controllers\Public;

use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Enums\PublicProductSort;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSite\ListProductsRequest;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use App\Queries\PublicSite\PublicProductListingQuery;
use App\Services\Facets\SiteFacetConfigResolver;
use App\Services\Pricing\MerchantFilterOptionsBuilder;
use App\Services\Pricing\ProductCardPricePresenter;
use App\Support\Facets\FacetUrlBuilder;
use Illuminate\Contracts\View\View;

final class ProductListingController extends Controller
{
    public function __invoke(
        ListProductsRequest $request,
        string $locale,
        string $slug,
        SiteContextResolver $sites,
        ThemeLayoutResolver $layouts,
        LocalizedUrlResolver $urls,
        PublicProductListingQuery $listingQuery,
        SiteFacetConfigResolver $facetConfig,
        FacetUrlBuilder $facetUrls,
        MerchantFilterOptionsBuilder $merchantOptions,
        ProductCardPricePresenter $pricePresenter,
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $listing = $request->listingData();
        $filters = $listing->filters;
        $result = $listingQuery->get(
            site: $site,
            locale: $locale,
            slug: $slug,
            filters: $filters,
            perPage: $listing->perPage,
        );
        $category = $result->category;
        $centralCategory = $result->centralCategory;
        $documents = $result->documents->withQueryString();
        $projections = $result->projections;
        $products = $documents->through(function (SiteSearchDocument $document) use (
            $projections,
            $site,
            $locale,
            $urls,
            $pricePresenter,
        ): array {
            $product = $projections->get($document->document_id);
            $price = $pricePresenter->present($document, $site->market->currency_code, $locale);

            if (! $product instanceof SiteProductProjection) {
                return [
                    'title' => $document->title,
                    'slug' => $document->slug,
                    'url' => filled($document->slug)
                        ? $urls->product($site, $locale, $document->slug)
                        : null,
                    'media' => data_get($document->payload_json, 'media', []),
                    'summary' => ['rating' => data_get($document->payload_json, 'rating')],
                    'price' => $price,
                ];
            }

            return [
                'title' => $product->title,
                'slug' => $product->slug,
                'url' => $urls->product($site, $locale, $product),
                'media' => $product->media_json ?? [],
                'summary' => $product->search_summary_json ?? [],
                'price' => $price,
            ];
        });
        $listingUrl = $urls->listing($site, $locale, $category);

        return view($layouts->resolve($site, 'listing'), [
            'site' => $site,
            'locale' => $locale,
            'category' => ['title' => $category->title, 'slug' => $category->slug],
            'products' => $products,
            'facets' => $facetConfig->resolve($site, $centralCategory),
            'merchants' => $merchantOptions->build($site, $centralCategory),
            'filters' => $filters,
            'appliedFilters' => $filters->appliedFilters(),
            'sort' => PublicProductSort::fromInput($filters->get('sort'))->value,
            'sortOptions' => PublicProductSort::options(),
            'currency' => $site->market->currency_code,
            'listingUrl' => $listingUrl,
            'clearFiltersUrl' => $facetUrls->clearAll($listingUrl),
            'categoryUrl' => $urls->category($site, $locale, $category),
            'seo' => [
                'meta_title' => $category->title.' products',
                'canonical_url' => $listingUrl,
                'robots' => $filters->hasActiveFilters() ? 'noindex,follow' : 'index,follow',
            ],
        ]);
    }
}

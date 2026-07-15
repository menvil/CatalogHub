<?php

namespace App\Http\Controllers\Public;

use App\Data\Facets\FacetFilterSet;
use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Enums\PublicProductSort;
use App\Http\Controllers\Controller;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use App\Services\Facets\FacetQueryBuilder;
use App\Services\Facets\SiteFacetConfigResolver;
use App\Services\Pricing\MerchantFilterOptionsBuilder;
use App\Services\Pricing\ProductCardPricePresenter;
use App\Support\Facets\FacetUrlBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ProductListingController extends Controller
{
    public function __invoke(
        Request $request,
        string $locale,
        string $slug,
        SiteContextResolver $sites,
        ThemeLayoutResolver $layouts,
        LocalizedUrlResolver $urls,
        FacetQueryBuilder $facetQuery,
        SiteFacetConfigResolver $facetConfig,
        FacetUrlBuilder $facetUrls,
        MerchantFilterOptionsBuilder $merchantOptions,
        ProductCardPricePresenter $pricePresenter,
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $site->loadMissing('market');
        $category = SiteCategoryProjection::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('status', ProjectionStatus::Active)
            ->firstOrFail();

        // Facet resolvers only require the category key; avoid hydrating central data in public runtime.
        $centralCategory = new CentralCategory;
        $centralCategory->setAttribute($centralCategory->getKeyName(), $category->central_category_id);
        $centralCategory->exists = true;
        $filters = FacetFilterSet::fromQuery($request->query());
        $query = $facetQuery->apply(
            SiteSearchDocument::query()->where('locale', $locale),
            $site,
            $centralCategory,
            $filters,
        );

        $perPage = max(1, min($request->integer('per_page', 12), 24));
        $documents = $query->paginate($perPage)->withQueryString();
        $projections = SiteProductProjection::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where('status', ProjectionStatus::Active)
            ->whereIn('central_product_id', $documents->getCollection()->pluck('document_id'))
            ->get()
            ->keyBy('central_product_id');
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

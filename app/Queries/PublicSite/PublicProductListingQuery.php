<?php

namespace App\Queries\PublicSite;

use App\Contracts\Persistence\StablePaginationBoundary;
use App\Data\Facets\FacetFilterSet;
use App\Data\PublicSite\PublicProductListingResult;
use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use App\Services\Facets\FacetQueryBuilder;

final readonly class PublicProductListingQuery implements StablePaginationBoundary
{
    public function __construct(
        private PublicCategoryQuery $categories,
        private FacetQueryBuilder $facets,
    ) {}

    public function get(
        Site $site,
        string $locale,
        string $slug,
        FacetFilterSet $filters,
        int $perPage,
        ?int $page = null,
    ): PublicProductListingResult {
        $category = $this->categories->findActive($site, $locale, $slug);

        // Facet resolvers only require the category key; avoid hydrating central data in public runtime.
        $centralCategory = new CentralCategory;
        $centralCategory->setAttribute($centralCategory->getKeyName(), $category->central_category_id);
        $centralCategory->exists = true;
        $documents = $this->facets->apply(
            SiteSearchDocument::query()->where('locale', $locale),
            $site,
            $centralCategory,
            $filters,
        )->paginate($perPage, ['*'], 'page', $page);
        $projections = SiteProductProjection::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where('status', ProjectionStatus::Active)
            ->whereIn('central_product_id', $documents->getCollection()->pluck('document_id'))
            ->get()
            ->keyBy('central_product_id');

        return new PublicProductListingResult(
            category: $category,
            centralCategory: $centralCategory,
            documents: $documents,
            projections: $projections,
        );
    }
}

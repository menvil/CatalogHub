<?php

namespace App\Services\Facets;

use App\Data\Facets\AppliedFacetFilter;
use App\Data\Facets\FacetDefinitionData;
use App\Data\Facets\FacetFilterSet;
use App\Data\Facets\FacetOptionData;
use App\Domains\Projections\Enums\ProjectionStatus;
use App\Enums\AttributeDataType;
use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use App\Enums\PublicProductSort;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use App\Queries\Facets\FacetDocumentExpressionQuery;
use App\Services\Pricing\MerchantFilterOptionsBuilder;
use App\Services\Pricing\ValidMarketOfferQuery;
use App\Support\Facets\BooleanFacetValueParser;
use App\Support\Facets\NumericRangeFacetParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class FacetQueryBuilder
{
    public function __construct(
        private SiteFacetConfigResolver $siteFacets,
        private BooleanFacetValueParser $booleans,
        private NumericRangeFacetParser $ranges,
        private MerchantFilterOptionsBuilder $merchantOptions,
        private ValidMarketOfferQuery $validOffers,
        private FacetDocumentExpressionQuery $expressions,
    ) {}

    /**
     * @param  Builder<SiteSearchDocument>  $query
     * @return Builder<SiteSearchDocument>
     */
    public function apply(
        Builder $query,
        Site $site,
        CentralCategory $category,
        FacetFilterSet $filters,
    ): Builder {
        $filters->clearAppliedFilters();
        $query
            ->where('site_id', $site->id)
            ->where('document_type', 'product')
            ->where('status', ProjectionStatus::Active)
            ->where('filter_values_json->category_id', $category->id);

        $facets = $this->siteFacets->resolve($site, $category);
        $this->retainKnownFilters($filters, $facets);
        $brands = $this->listValues($filters->get('brand'));

        if ($brands !== []) {
            $filters->replace('brand', $brands);
            $query->where(function (Builder $brandQuery) use ($brands): void {
                foreach ($brands as $brand) {
                    $brandQuery->orWhere('filter_values_json->brand_slug', $brand);
                }
            });

            foreach ($brands as $brand) {
                $filters->recordAppliedFilter(new AppliedFacetFilter(
                    code: 'brand',
                    label: strlen($brand) <= 3 ? Str::upper($brand) : Str::headline($brand),
                    value: $brand,
                    queryKeys: ['brand'],
                ));
            }
        } else {
            $filters->forget('brand');
        }

        $this->applyMerchantFilter($query, $site, $category, $filters);
        $this->applyEnumFilters($query, $facets, $filters);
        $this->applyBooleanFilters($query, $facets, $filters);
        $this->applyNumericRangeFilters($query, $facets, $filters);
        $this->applyPriceRangeFilter($query, $filters);
        $this->applyInStockFilter($query, $filters);
        $this->applyRatingFilter($query, $filters);
        $this->applySorting($query, $filters);

        return $query;
    }

    /** @param Collection<int, FacetDefinitionData> $facets */
    private function retainKnownFilters(FacetFilterSet $filters, Collection $facets): void
    {
        $keys = ['brand', 'in_stock', 'merchant_ids', 'price_from', 'price_to', 'rating_min', 'sort'];

        foreach ($facets as $facet) {
            if ($this->isNumericRangeFacet($facet)) {
                $keys[] = "{$facet->code}_min";
                $keys[] = "{$facet->code}_max";
            } elseif ($facet->sourceType === FacetSourceType::Rating) {
                $keys[] = 'rating_min';
            } elseif ($this->isEnumFacet($facet) || $this->isBooleanFacet($facet)) {
                $keys[] = $facet->code;
            }
        }

        $filters->retain(array_values(array_unique($keys)));
    }

    /** @param Builder<SiteSearchDocument> $query */
    private function applyEnumFilters(
        Builder $query,
        Collection $facets,
        FacetFilterSet $filters,
    ): void {
        $facets = $facets
            ->filter(fn (FacetDefinitionData $facet): bool => $this->isEnumFacet($facet));

        foreach ($facets as $facet) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', $facet->code) !== 1) {
                continue;
            }

            $values = $this->allowedOptionValues($facet, $this->listValues($filters->get($facet->code)));

            if ($facet->type === FacetType::Select) {
                $values = array_slice($values, 0, 1);
            }

            if ($values === []) {
                $filters->forget($facet->code);

                continue;
            }

            $filters->replace(
                $facet->code,
                $facet->type === FacetType::Select ? $values[0] : $values,
            );

            $query->where(function (Builder $facetQuery) use ($facet, $values): void {
                foreach ($values as $value) {
                    $facetQuery
                        ->orWhere("filter_values_json->{$facet->code}", $value)
                        ->orWhereJsonContains("filter_values_json->{$facet->code}", $value);
                }
            });

            foreach ($values as $value) {
                $option = collect($facet->options)->first(
                    fn (FacetOptionData $option): bool => Str::lower(trim($option->value)) === $value,
                );
                $filters->recordAppliedFilter(new AppliedFacetFilter(
                    code: $facet->code,
                    label: $option instanceof FacetOptionData ? $option->label : Str::headline($value),
                    value: $value,
                    queryKeys: [$facet->code],
                ));
            }
        }
    }

    /**
     * @param  Builder<SiteSearchDocument>  $query
     * @param  Collection<int, FacetDefinitionData>  $facets
     */
    private function applyBooleanFilters(
        Builder $query,
        Collection $facets,
        FacetFilterSet $filters,
    ): void {
        $booleanFacets = $facets->filter(
            fn (FacetDefinitionData $facet): bool => $this->isBooleanFacet($facet),
        );

        foreach ($booleanFacets as $facet) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', $facet->code) !== 1 || ! $filters->has($facet->code)) {
                continue;
            }

            $value = $this->booleans->parse($filters->get($facet->code));

            if ($value === null) {
                $filters->forget($facet->code);

                continue;
            }

            $filters->replace($facet->code, $this->booleans->serialize($value));
            $query->where("filter_values_json->{$facet->code}", $value);
            $filters->recordAppliedFilter(new AppliedFacetFilter(
                code: $facet->code,
                label: $value ? 'Yes' : 'No',
                value: $this->booleans->serialize($value),
                queryKeys: [$facet->code],
            ));
        }
    }

    /**
     * @param  Builder<SiteSearchDocument>  $query
     * @param  Collection<int, FacetDefinitionData>  $facets
     */
    private function applyNumericRangeFilters(
        Builder $query,
        Collection $facets,
        FacetFilterSet $filters,
    ): void {
        $rangeFacets = $facets->filter(
            fn (FacetDefinitionData $facet): bool => $this->isNumericRangeFacet($facet),
        );

        foreach ($rangeFacets as $facet) {
            $minimumKey = "{$facet->code}_min";
            $maximumKey = "{$facet->code}_max";

            if (preg_match('/^[a-zA-Z0-9_]+$/', $facet->code) !== 1
                || (! $filters->has($minimumKey) && ! $filters->has($maximumKey))) {
                continue;
            }

            $range = $this->ranges->parse(
                $filters->get($minimumKey),
                $filters->get($maximumKey),
            );

            if ($range === null) {
                $filters->forget($minimumKey, $maximumKey);

                continue;
            }

            if ($range['min'] === null) {
                $filters->forget($minimumKey);
            } else {
                $filters->replace($minimumKey, $this->ranges->serialize($range['min']));
            }

            if ($range['max'] === null) {
                $filters->forget($maximumKey);
            } else {
                $filters->replace($maximumKey, $this->ranges->serialize($range['max']));
            }

            if ($range['min'] !== null) {
                $this->applyNumericConstraint($query, 'filter_values_json', $facet->code, '>=', $range['min']);
            }

            if ($range['max'] !== null) {
                $this->applyNumericConstraint($query, 'filter_values_json', $facet->code, '<=', $range['max']);
            }

            $filters->recordAppliedFilter(new AppliedFacetFilter(
                code: $facet->code,
                label: $facet->label,
                value: array_filter([
                    'min' => $range['min'] === null ? null : $this->ranges->serialize($range['min']),
                    'max' => $range['max'] === null ? null : $this->ranges->serialize($range['max']),
                ], fn (?string $value): bool => $value !== null),
                queryKeys: [$minimumKey, $maximumKey],
            ));
        }
    }

    /** @param Builder<SiteSearchDocument> $query */
    private function applyRatingFilter(Builder $query, FacetFilterSet $filters): void
    {
        if (! $filters->has('rating_min')) {
            return;
        }

        $range = $this->ranges->parse($filters->get('rating_min'), null);

        if ($range === null || $range['min'] === null) {
            $filters->forget('rating_min');

            return;
        }

        $minimum = max(0.0, min(5.0, $range['min']));
        $filters->replace('rating_min', $this->ranges->serialize($minimum));
        $this->applyNumericConstraint($query, 'sort_values_json', 'rating', '>=', $minimum);
        $filters->recordAppliedFilter(new AppliedFacetFilter(
            code: 'rating',
            label: 'Rating',
            value: $this->ranges->serialize($minimum),
            queryKeys: ['rating_min'],
        ));
    }

    /** @param Builder<SiteSearchDocument> $query */
    private function applyPriceRangeFilter(Builder $query, FacetFilterSet $filters): void
    {
        if (! $filters->has('price_from') && ! $filters->has('price_to')) {
            return;
        }

        $range = $this->ranges->parse($filters->get('price_from'), $filters->get('price_to'));

        if ($range === null) {
            $filters->forget('price_from', 'price_to');

            return;
        }

        $minimum = $range['min'] === null ? null : max(0.0, $range['min']);
        $maximum = $range['max'] === null ? null : max(0.0, $range['max']);

        if ($minimum === null) {
            $filters->forget('price_from');
        } else {
            $filters->replace('price_from', $this->ranges->serialize($minimum));
        }

        if ($maximum === null) {
            $filters->forget('price_to');
        } else {
            $filters->replace('price_to', $this->ranges->serialize($maximum));
        }

        $query->whereNotNull('min_price');

        if ($minimum !== null) {
            $query->where('min_price', '>=', $minimum);
        }

        if ($maximum !== null) {
            $query->where('min_price', '<=', $maximum);
        }

        $filters->recordAppliedFilter(new AppliedFacetFilter(
            code: 'price',
            label: 'Price',
            value: array_filter([
                'from' => $minimum === null ? null : $this->ranges->serialize($minimum),
                'to' => $maximum === null ? null : $this->ranges->serialize($maximum),
            ], fn (?string $value): bool => $value !== null),
            queryKeys: ['price_from', 'price_to'],
        ));
    }

    /** @param Builder<SiteSearchDocument> $query */
    private function applyInStockFilter(Builder $query, FacetFilterSet $filters): void
    {
        if (! $filters->has('in_stock') || $this->booleans->parse($filters->get('in_stock')) !== true) {
            $filters->forget('in_stock');

            return;
        }

        $filters->replace('in_stock', '1');
        $query->where('in_stock', true);
        $filters->recordAppliedFilter(new AppliedFacetFilter(
            code: 'in_stock',
            label: 'In stock',
            value: '1',
            queryKeys: ['in_stock'],
        ));
    }

    /** @param Builder<SiteSearchDocument> $query */
    private function applyMerchantFilter(
        Builder $query,
        Site $site,
        CentralCategory $category,
        FacetFilterSet $filters,
    ): void {
        if (! $filters->has('merchant_ids')) {
            return;
        }

        $options = $this->merchantOptions->build($site, $category)->keyBy('id');
        $merchantIds = collect($this->listValues($filters->get('merchant_ids')))
            ->filter(fn (string $id): bool => ctype_digit($id) && (int) $id > 0)
            ->map(fn (string $id): int => (int) $id)
            ->filter(fn (int $id): bool => $options->has($id))
            ->unique()
            ->sort()
            ->values();

        if ($merchantIds->isEmpty()) {
            $filters->forget('merchant_ids');

            return;
        }

        $filters->replace('merchant_ids', $merchantIds->map(fn (int $id): string => (string) $id)->all());
        $productIds = $this->validOffers->forSite($site)
            ->select('central_product_id')
            ->whereIn('market_merchant_id', $merchantIds->all())
            ->distinct();
        $query->whereIn('document_id', $productIds);

        foreach ($merchantIds as $merchantId) {
            $merchant = $options->get($merchantId);
            $filters->recordAppliedFilter(new AppliedFacetFilter(
                code: 'merchant',
                label: (string) $merchant?->getAttribute('name'),
                value: (string) $merchantId,
                queryKeys: ['merchant_ids'],
            ));
        }
    }

    /** @param Builder<SiteSearchDocument> $query */
    private function applySorting(Builder $query, FacetFilterSet $filters): void
    {
        $sort = PublicProductSort::fromInput($filters->get('sort'));

        if ($filters->has('sort')) {
            $filters->replace('sort', $sort->value);
        }

        match ($sort) {
            PublicProductSort::RatingDesc => $this->expressions->orderByRatingDesc($query),
            PublicProductSort::NameAsc => $query->orderBy('title')->orderBy('id'),
            PublicProductSort::NameDesc => $query->orderByDesc('title')->orderByDesc('id'),
            PublicProductSort::PriceAsc => $this->expressions->orderByPriceAsc($query),
            PublicProductSort::PriceDesc => $this->expressions->orderByPriceDesc($query),
            PublicProductSort::Default,
            PublicProductSort::Newest => $query->orderByDesc('built_at')->orderByDesc('id'),
        };
    }

    /** @param Builder<SiteSearchDocument> $query */
    private function applyNumericConstraint(
        Builder $query,
        string $column,
        string $code,
        string $operator,
        float $value,
    ): void {
        $this->expressions->whereNumeric(
            $query,
            $column,
            $code,
            $operator,
            $this->ranges->serialize($value),
        );
    }

    /**
     * @param  list<string>  $values
     * @return list<string>
     */
    private function allowedOptionValues(FacetDefinitionData $facet, array $values): array
    {
        if ($facet->options === []) {
            return $values;
        }

        $allowed = $this->listValues(collect($facet->options)->pluck('value')->all());

        return array_values(array_intersect($values, $allowed));
    }

    private function isEnumFacet(FacetDefinitionData $facet): bool
    {
        return $facet->sourceType === FacetSourceType::Attribute
            && in_array($facet->attributeDataType, [AttributeDataType::Enum, AttributeDataType::MultiEnum], true)
            && in_array($facet->type, [FacetType::Checkbox, FacetType::Select], true);
    }

    private function isBooleanFacet(FacetDefinitionData $facet): bool
    {
        return $facet->type === FacetType::Boolean
            && $facet->sourceType === FacetSourceType::Attribute
            && $facet->attributeDataType === AttributeDataType::Boolean;
    }

    private function isNumericRangeFacet(FacetDefinitionData $facet): bool
    {
        return $facet->type === FacetType::Range
            && $facet->sourceType === FacetSourceType::Attribute
            && in_array($facet->attributeDataType, [AttributeDataType::Integer, AttributeDataType::Decimal], true);
    }

    /** @return list<string> */
    private function listValues(mixed $value): array
    {
        $values = is_string($value) ? explode(',', $value) : Arr::wrap($value);

        return collect($values)
            ->filter(fn (mixed $item): bool => is_scalar($item))
            ->map(fn (mixed $item): string => Str::lower(trim((string) $item)))
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
}

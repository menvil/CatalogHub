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
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use App\Support\Facets\BooleanFacetValueParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final readonly class FacetQueryBuilder
{
    public function __construct(
        private SiteFacetConfigResolver $siteFacets,
        private BooleanFacetValueParser $booleans,
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

        $brands = $this->listValues($filters->get('brand'));

        if ($brands !== []) {
            $query->where(function (Builder $brandQuery) use ($brands): void {
                foreach ($brands as $brand) {
                    $brandQuery->orWhere('filter_values_json->brand_slug', $brand);
                }
            });

            foreach ($brands as $brand) {
                $filters->recordAppliedFilter(new AppliedFacetFilter(
                    code: 'brand',
                    label: Str::headline($brand),
                    value: $brand,
                    queryKeys: ['brand'],
                ));
            }
        }

        $facets = $this->siteFacets->resolve($site, $category);
        $this->applyEnumFilters($query, $facets, $filters);
        $this->applyBooleanFilters($query, $facets, $filters);

        return $query;
    }

    /** @param Builder<SiteSearchDocument> $query */
    private function applyEnumFilters(
        Builder $query,
        Collection $facets,
        FacetFilterSet $filters,
    ): void {
        $facets = $facets
            ->filter(fn (FacetDefinitionData $facet): bool => $facet->sourceType === FacetSourceType::Attribute
                && in_array($facet->attributeDataType, [AttributeDataType::Enum, AttributeDataType::MultiEnum], true)
                && in_array($facet->type, [FacetType::Checkbox, FacetType::Select], true));

        foreach ($facets as $facet) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', $facet->code) !== 1) {
                continue;
            }

            $values = $this->allowedOptionValues($facet, $this->listValues($filters->get($facet->code)));

            if ($facet->type === FacetType::Select) {
                $values = array_slice($values, 0, 1);
            }

            if ($values === []) {
                continue;
            }

            $query->where(function (Builder $facetQuery) use ($facet, $values): void {
                foreach ($values as $value) {
                    $facetQuery
                        ->orWhere("filter_values_json->{$facet->code}", $value)
                        ->orWhereJsonContains("filter_values_json->{$facet->code}", $value);
                }
            });

            foreach ($values as $value) {
                $option = collect($facet->options)->first(
                    fn (FacetOptionData $option): bool => $option->value === $value,
                );
                $filters->recordAppliedFilter(new AppliedFacetFilter(
                    code: $facet->code,
                    label: $option?->label ?? Str::headline($value),
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
            fn (FacetDefinitionData $facet): bool => $facet->type === FacetType::Boolean
                && $facet->sourceType === FacetSourceType::Attribute
                && $facet->attributeDataType === AttributeDataType::Boolean,
        );

        foreach ($booleanFacets as $facet) {
            if (preg_match('/^[a-zA-Z0-9_]+$/', $facet->code) !== 1 || ! $filters->has($facet->code)) {
                continue;
            }

            $value = $this->booleans->parse($filters->get($facet->code));

            if ($value === null) {
                continue;
            }

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
     * @param  list<string>  $values
     * @return list<string>
     */
    private function allowedOptionValues(FacetDefinitionData $facet, array $values): array
    {
        if ($facet->options === []) {
            return $values;
        }

        $allowed = collect($facet->options)->pluck('value')->all();

        return array_values(array_intersect($values, $allowed));
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

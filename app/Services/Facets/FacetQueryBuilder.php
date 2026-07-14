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

        $this->applyEnumFilters($query, $facets, $filters);
        $this->applyBooleanFilters($query, $facets, $filters);
        $this->applyNumericRangeFilters($query, $facets, $filters);
        $this->applyRatingFilter($query, $filters);
        $this->applySorting($query, $filters);

        return $query;
    }

    /** @param Collection<int, FacetDefinitionData> $facets */
    private function retainKnownFilters(FacetFilterSet $filters, Collection $facets): void
    {
        $keys = ['brand', 'rating_min', 'sort'];

        foreach ($facets as $facet) {
            if ($facet->type === FacetType::Range && $facet->sourceType !== FacetSourceType::Rating) {
                $keys[] = "{$facet->code}_min";
                $keys[] = "{$facet->code}_max";
            } elseif ($facet->sourceType === FacetSourceType::Rating) {
                $keys[] = 'rating_min';
            } else {
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
                    fn (FacetOptionData $option): bool => $option->value === $value,
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
            fn (FacetDefinitionData $facet): bool => $facet->type === FacetType::Range
                && $facet->sourceType === FacetSourceType::Attribute
                && in_array($facet->attributeDataType, [AttributeDataType::Integer, AttributeDataType::Decimal], true),
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
    private function applySorting(Builder $query, FacetFilterSet $filters): void
    {
        $sort = PublicProductSort::fromInput($filters->get('sort'));

        if ($filters->has('sort')) {
            $filters->replace('sort', $sort->value);
        }

        match ($sort) {
            PublicProductSort::RatingDesc => $query
                ->orderByRaw($this->numericJsonValueExpression($query, 'sort_values_json', 'rating').' DESC')
                ->orderByDesc('id'),
            PublicProductSort::NameAsc => $query->orderBy('title')->orderBy('id'),
            PublicProductSort::NameDesc => $query->orderByDesc('title')->orderByDesc('id'),
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
        $serialized = $this->ranges->serialize($value);
        $driver = $query->getModel()->getConnection()->getDriverName();

        $valueExpression = $this->numericJsonValueExpression($query, $column, $code);
        $placeholder = match ($driver) {
            'mysql', 'mariadb' => 'CAST(? AS DECIMAL(65, 20))',
            'pgsql' => 'CAST(? AS NUMERIC)',
            'sqlsrv' => 'TRY_CAST(? AS FLOAT)',
            default => 'CAST(? AS REAL)',
        };

        $query->whereRaw("{$valueExpression} {$operator} {$placeholder}", [$serialized]);
    }

    /** @param Builder<SiteSearchDocument> $query */
    private function numericJsonValueExpression(Builder $query, string $column, string $code): string
    {
        return match ($query->getModel()->getConnection()->getDriverName()) {
            'mysql', 'mariadb' => "CAST(JSON_UNQUOTE(JSON_EXTRACT(`{$column}`, '$.\"{$code}\"')) AS DECIMAL(65, 20))",
            'pgsql' => "CAST(\"{$column}\"->>'{$code}' AS NUMERIC)",
            'sqlsrv' => "TRY_CAST(JSON_VALUE([{$column}], '$.{$code}') AS FLOAT)",
            default => "CAST(json_extract(\"{$column}\", '$.\"{$code}\"') AS REAL)",
        };
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

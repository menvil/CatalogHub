<?php

namespace App\Services\Facets;

use App\Data\Facets\AppliedFacetFilter;
use App\Data\Facets\FacetFilterSet;
use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

final class FacetQueryBuilder
{
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

        return $query;
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

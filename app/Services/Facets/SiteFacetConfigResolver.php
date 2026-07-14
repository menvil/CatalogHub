<?php

namespace App\Services\Facets;

use App\Data\Facets\FacetDefinitionData;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Models\SiteFacetOverride;
use Illuminate\Support\Collection;

final readonly class SiteFacetConfigResolver
{
    public function __construct(
        private CategoryFacetConfigResolver $categoryFacets,
    ) {}

    /** @return Collection<int, FacetDefinitionData> */
    public function resolve(Site $site, CentralCategory $category): Collection
    {
        $facets = $this->categoryFacets->resolve($category);

        if ($facets->isEmpty()) {
            return $facets;
        }

        $overrides = SiteFacetOverride::query()
            ->where('site_id', $site->id)
            ->whereIn('facet_definition_id', $facets->pluck('id'))
            ->get()
            ->keyBy('facet_definition_id');

        return $facets
            ->reject(fn (FacetDefinitionData $facet): bool => $overrides->get($facet->id)?->is_visible === false)
            ->map(function (FacetDefinitionData $facet) use ($overrides): FacetDefinitionData {
                $override = $overrides->get($facet->id);

                return $override instanceof SiteFacetOverride
                    ? $this->applyOverride($facet, $override)
                    : $facet;
            })
            ->sort(fn (FacetDefinitionData $left, FacetDefinitionData $right): int => [
                $left->position,
                $left->id,
            ] <=> [
                $right->position,
                $right->id,
            ])
            ->values();
    }

    private function applyOverride(
        FacetDefinitionData $facet,
        SiteFacetOverride $override,
    ): FacetDefinitionData {
        return new FacetDefinitionData(
            id: $facet->id,
            code: $facet->code,
            label: $override->label_override ?: $facet->label,
            type: $facet->type,
            sourceType: $facet->sourceType,
            position: $override->position_override ?? $facet->position,
            isCollapsible: $facet->isCollapsible,
            defaultCollapsed: $override->default_collapsed ?? $facet->defaultCollapsed,
            config: array_replace($facet->config, $override->config_json ?? []),
            options: $facet->options,
            attributeCode: $facet->attributeCode,
            attributeDataType: $facet->attributeDataType,
            canonicalUnit: $facet->canonicalUnit,
        );
    }
}

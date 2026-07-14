<?php

namespace App\Services\Facets;

use App\Data\Facets\FacetDefinitionData;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use Illuminate\Support\Collection;

final class CategoryFacetConfigResolver
{
    /** @return Collection<int, FacetDefinitionData> */
    public function resolve(CentralCategory $category): Collection
    {
        return FacetDefinition::query()
            ->forCategory($category)
            ->active()
            ->where('is_visible', true)
            ->with(['attributeDefinition', 'activeOptions'])
            ->ordered()
            ->get()
            ->map(fn (FacetDefinition $facet): FacetDefinitionData => FacetDefinitionData::fromModel($facet));
    }
}

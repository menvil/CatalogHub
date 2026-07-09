<?php

namespace App\Actions\CategorySchema;

use App\Actions\CategorySchema\Concerns\ValidatesAttributeDefinitionData;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class CreateAttributeDefinitionAction
{
    use ValidatesAttributeDefinitionData;

    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(AttributeSection $section, array $data): AttributeDefinition
    {
        $validated = Validator::make($data, $this->validationRules($section->central_category_id))->validate();

        return DB::transaction(function () use ($section, $validated): AttributeDefinition {
            $section->newQuery()->whereKey($section->getKey())->lockForUpdate()->firstOrFail();

            $position = $validated['position']
                ?? ((int) $section->attributes()->max('position') + 1);

            return AttributeDefinition::query()->create([
                'central_category_id' => $section->central_category_id,
                'attribute_section_id' => $section->getKey(),
                'code' => $validated['code'],
                'name' => $validated['name'],
                'data_type' => $validated['data_type'],
                'dimension' => $validated['dimension'] ?? null,
                'canonical_unit' => $validated['canonical_unit'] ?? null,
                'position' => $position,
                'is_required' => $validated['is_required'] ?? false,
                'is_filterable' => $validated['is_filterable'] ?? false,
                'is_sortable' => $validated['is_sortable'] ?? false,
                'is_comparable' => $validated['is_comparable'] ?? false,
                'is_visible' => $validated['is_visible'] ?? true,
                'is_searchable' => $validated['is_searchable'] ?? false,
            ]);
        });
    }
}

<?php

namespace App\Actions\CategorySchema;

use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CreateAttributeDefinitionAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function handle(AttributeSection $section, array $data): AttributeDefinition
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('attribute_definitions', 'code')
                    ->where('central_category_id', $section->central_category_id),
            ],
            'data_type' => ['required', Rule::enum(AttributeDataType::class)],
            'dimension' => ['nullable', 'string', 'max:255'],
            'canonical_unit' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_required' => ['nullable', 'boolean'],
            'is_filterable' => ['nullable', 'boolean'],
            'is_sortable' => ['nullable', 'boolean'],
            'is_comparable' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
            'is_searchable' => ['nullable', 'boolean'],
        ])->validate();

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
    }
}

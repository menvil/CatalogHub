<?php

namespace App\Actions\CategorySchema;

use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class UpdateAttributeDefinitionAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(AttributeDefinition $attribute, array $data): AttributeDefinition
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/\A[a-z][a-z0-9_]*\z/',
                Rule::unique('attribute_definitions', 'code')
                    ->where('central_category_id', $attribute->central_category_id)
                    ->ignore($attribute->getKey()),
            ],
            'data_type' => ['required', Rule::enum(AttributeDataType::class)],
            'dimension' => ['nullable', 'string', 'max:255'],
            'canonical_unit' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0', 'max:'.AttributeDefinition::MAX_POSITION],
            'is_required' => ['nullable', 'boolean'],
            'is_filterable' => ['nullable', 'boolean'],
            'is_sortable' => ['nullable', 'boolean'],
            'is_comparable' => ['nullable', 'boolean'],
            'is_visible' => ['nullable', 'boolean'],
            'is_searchable' => ['nullable', 'boolean'],
        ])->validate();

        $dataType = $validated['data_type'] instanceof AttributeDataType
            ? $validated['data_type']
            : AttributeDataType::from($validated['data_type']);

        if (! $dataType->allowsOptions() && $attribute->options()->exists()) {
            throw ValidationException::withMessages([
                'data_type' => 'Attributes with options can only use enum or multi_enum data types.',
            ]);
        }

        $attribute->update([
            'code' => $validated['code'],
            'name' => $validated['name'],
            'data_type' => $dataType,
            'dimension' => $validated['dimension'] ?? null,
            'canonical_unit' => $validated['canonical_unit'] ?? null,
            'position' => $validated['position'] ?? $attribute->position,
            'is_required' => $validated['is_required'] ?? $attribute->is_required,
            'is_filterable' => $validated['is_filterable'] ?? $attribute->is_filterable,
            'is_sortable' => $validated['is_sortable'] ?? $attribute->is_sortable,
            'is_comparable' => $validated['is_comparable'] ?? $attribute->is_comparable,
            'is_visible' => $validated['is_visible'] ?? $attribute->is_visible,
            'is_searchable' => $validated['is_searchable'] ?? $attribute->is_searchable,
        ]);

        return $attribute;
    }
}

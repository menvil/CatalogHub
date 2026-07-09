<?php

namespace App\Actions\CategorySchema\Concerns;

use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use Illuminate\Validation\Rule;

trait ValidatesAttributeDefinitionData
{
    /**
     * @return array<string, mixed>
     */
    private function validationRules(int $categoryId, ?int $ignoreAttributeId = null): array
    {
        $codeRule = Rule::unique('attribute_definitions', 'code')
            ->where('central_category_id', $categoryId);

        if ($ignoreAttributeId !== null) {
            $codeRule->ignore($ignoreAttributeId);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/\A[a-z][a-z0-9_]*\z/',
                $codeRule,
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
        ];
    }
}

<?php

namespace App\Actions\CategorySchema;

use App\Exceptions\CategorySchema\CannotManageAttributeOptionException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CreateAttributeOptionAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(AttributeDefinition $attribute, array $data): AttributeOption
    {
        if (! $attribute->data_type->allowsOptions()) {
            throw CannotManageAttributeOptionException::attributeDoesNotAllowOptions();
        }

        $validated = Validator::make($data, [
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('attribute_options', 'code')
                    ->where('attribute_definition_id', $attribute->getKey()),
            ],
            'label' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
        ])->validate();

        $position = $validated['position']
            ?? ((int) $attribute->options()->max('position') + 1);

        return AttributeOption::query()->create([
            'attribute_definition_id' => $attribute->getKey(),
            'code' => $validated['code'],
            'label' => $validated['label'],
            'position' => $position,
            'is_visible' => $validated['is_visible'] ?? true,
        ]);
    }
}

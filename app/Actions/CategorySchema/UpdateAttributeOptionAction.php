<?php

namespace App\Actions\CategorySchema;

use App\Exceptions\CategorySchema\CannotManageAttributeOptionException;
use App\Models\CentralCatalog\AttributeOption;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class UpdateAttributeOptionAction
{
    /**
     * @param array<string, mixed> $data
     */
    public function handle(AttributeOption $option, array $data): AttributeOption
    {
        if (! $option->attribute->data_type->allowsOptions()) {
            throw CannotManageAttributeOptionException::attributeDoesNotAllowOptions();
        }

        $validated = Validator::make($data, [
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/^[a-z][a-z0-9_]*$/',
                Rule::unique('attribute_options', 'code')
                    ->where('attribute_definition_id', $option->attribute_definition_id)
                    ->ignore($option->getKey()),
            ],
            'label' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
            'is_visible' => ['nullable', 'boolean'],
        ])->validate();

        $option->update([
            'code' => $validated['code'],
            'label' => $validated['label'],
            'position' => $validated['position'] ?? $option->position,
            'is_visible' => $validated['is_visible'] ?? $option->is_visible,
        ]);

        return $option;
    }
}

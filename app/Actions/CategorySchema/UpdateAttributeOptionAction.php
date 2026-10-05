<?php

namespace App\Actions\CategorySchema;

use App\Enums\SchemaMutationOrigin;
use App\Exceptions\CategorySchema\CannotManageAttributeOptionException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\User;
use App\Services\CategorySchema\SchemaRevision;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class UpdateAttributeOptionAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(AttributeOption $option, array $data, ?User $actor = null): AttributeOption
    {
        // Resolve current ownership without trusting the caller's cached relation.
        // perform() reloads the option/type again after the Category lock.
        $categoryId = AttributeDefinition::query()->whereIn('id',
            AttributeOption::query()->select('attribute_definition_id')->whereKey($option->id)
        )->firstOrFail(['central_category_id'])->central_category_id;

        return app(SchemaRevision::class)->mutate($categoryId, SchemaMutationOrigin::OptionUpdated, $option->id, fn () => $this->perform($option, $data), $actor);
    }

    private function perform(AttributeOption $option, array $data): AttributeOption
    {
        $option = AttributeOption::query()->findOrFail($option->id);

        if (! $option->attribute->data_type->allowsOptions()) {
            throw CannotManageAttributeOptionException::attributeDoesNotAllowOptions();
        }

        $validated = Validator::make($data, [
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/\A[a-z][a-z0-9_]*\z/',
                Rule::unique('attribute_options', 'code')
                    ->where('attribute_definition_id', $option->attribute_definition_id)
                    ->ignore($option->getKey()),
            ],
            'label' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0', 'max:'.AttributeOption::MAX_POSITION],
            'is_visible' => ['nullable', 'boolean'],
        ])->validate();

        if ($validated['code'] !== $option->code) {
            throw ValidationException::withMessages(['code' => 'Option code is immutable. Explicit reconciliation required to change option identity.']);
        }

        $option->fill([
            'code' => $validated['code'],
            'label' => $validated['label'],
            'position' => $validated['position'] ?? $option->position,
            'is_visible' => $validated['is_visible'] ?? $option->is_visible,
        ]);

        if ($option->isDirty()) {
            $option->saveOrFail();
        }

        return $option;
    }
}

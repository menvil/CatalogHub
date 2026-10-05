<?php

namespace App\Actions\CategorySchema;

use App\Enums\SchemaMutationOrigin;
use App\Exceptions\CategorySchema\CannotManageAttributeOptionException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\User;
use App\Services\AttributeGlobalization\AttributeIdentityReservation;
use App\Services\CategorySchema\SchemaRevision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

final class CreateAttributeOptionAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(AttributeDefinition $attribute, array $data, ?User $actor = null): AttributeOption
    {
        $categoryId = $attribute->central_category_id;

        return app(SchemaRevision::class)->mutate($categoryId, SchemaMutationOrigin::OptionCreated, $attribute->id, fn () => $this->perform($attribute, $data), $actor);
    }

    private function perform(AttributeDefinition $attribute, array $data): AttributeOption
    {
        $attribute = AttributeDefinition::query()->findOrFail($attribute->id);

        if (! $attribute->data_type->allowsOptions()) {
            throw CannotManageAttributeOptionException::attributeDoesNotAllowOptions();
        }

        $validated = Validator::make($data, [
            'code' => [
                'required',
                'string',
                'max:255',
                'regex:/\A[a-z][a-z0-9_]*\z/',
                Rule::unique('attribute_options', 'code')
                    ->where('attribute_definition_id', $attribute->getKey()),
            ],
            'label' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0', 'max:'.AttributeOption::MAX_POSITION],
            'is_visible' => ['nullable', 'boolean'],
        ])->validate();

        return DB::transaction(function () use ($attribute, $validated): AttributeOption {
            $attribute->newQuery()->whereKey($attribute->getKey())->lockForUpdate()->firstOrFail();

            app(AttributeIdentityReservation::class)->optionCode($attribute->id, $validated['code']);

            $position = $validated['position']
                ?? ((int) $attribute->options()->max('position') + 1);

            if ($position > AttributeOption::MAX_POSITION) {
                throw ValidationException::withMessages([
                    'position' => 'The position may not be greater than '.AttributeOption::MAX_POSITION.'.',
                ]);
            }

            return AttributeOption::query()->create([
                'attribute_definition_id' => $attribute->getKey(),
                'code' => $validated['code'],
                'label' => $validated['label'],
                'position' => $position,
                'is_visible' => $validated['is_visible'] ?? true,
            ]);
        });
    }
}

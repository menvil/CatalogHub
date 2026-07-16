<?php

namespace App\Services\Corrections;

use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CanonicalCorrectionFieldResolver
{
    /** @var array<string, string> */
    public const PRODUCT_FIELDS = [
        'name' => 'Canonical name',
        'model' => 'Model',
        'slug' => 'Canonical slug',
    ];

    public function supports(string $fieldPath): bool
    {
        return array_key_exists($fieldPath, self::PRODUCT_FIELDS);
    }

    public function currentValue(CentralProduct $product, string $fieldPath): mixed
    {
        return $product->getAttribute($fieldPath);
    }

    public function apply(CentralProduct $product, string $fieldPath, mixed $proposedValue): void
    {
        $rule = match ($fieldPath) {
            'name' => ['required', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'lowercase',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique('central_products', 'slug')->ignore($product->getKey()),
            ],
            default => ['prohibited'],
        };

        $validated = Validator::make(
            ['value' => $proposedValue],
            ['value' => $rule],
        )->validate();

        $product->forceFill([$fieldPath => $validated['value']])->save();
    }
}

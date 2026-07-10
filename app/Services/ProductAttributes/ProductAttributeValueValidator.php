<?php

namespace App\Services\ProductAttributes;

use App\Enums\AttributeDataType;
use App\Exceptions\ProductAttributes\CannotSaveProductSpecsException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralProduct;
use Illuminate\Support\Collection;

final class ProductAttributeValueValidator
{
    /**
     * @param  array<int|string, array<string, mixed>>  $payload
     * @return array<int, array<string, mixed>>
     */
    public function validate(CentralProduct $product, array $payload): array
    {
        $product->loadMissing([
            'category.attributeDefinitions.options',
        ]);

        if (! $product->category) {
            throw CannotSaveProductSpecsException::because('Product must have a category before specs can be saved.');
        }

        $attributes = $product->category->attributeDefinitions;
        $validated = [];

        foreach ($payload as $attributeKey => $valueData) {
            if (! is_array($valueData)) {
                throw CannotSaveProductSpecsException::because("Attribute [{$attributeKey}] payload must be an array.");
            }

            $attribute = $this->resolveAttribute($attributes, $attributeKey);
            $normalized = $this->validateAttribute($attribute, $valueData);
            $validated[(int) $attribute->id] = $normalized;
        }

        return $validated;
    }

    /**
     * @param  Collection<int, AttributeDefinition>  $attributes
     */
    private function resolveAttribute(Collection $attributes, int|string $attributeKey): AttributeDefinition
    {
        $attribute = is_numeric($attributeKey)
            ? $attributes->firstWhere('id', (int) $attributeKey)
            : $attributes->firstWhere('code', (string) $attributeKey);

        if (! $attribute instanceof AttributeDefinition) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attributeKey}] is not part of the product category schema.");
        }

        return $attribute;
    }

    /**
     * @param  array<string, mixed>  $valueData
     * @return array<string, mixed>
     */
    private function validateAttribute(AttributeDefinition $attribute, array $valueData): array
    {
        $normalized = $this->baseValueData($attribute, $valueData);

        match ($attribute->data_type) {
            AttributeDataType::Integer, AttributeDataType::Decimal => $this->validateNumeric($attribute, $normalized),
            AttributeDataType::String, AttributeDataType::Text => $this->validateText($attribute, $normalized),
            AttributeDataType::Boolean => $this->validateBoolean($attribute, $normalized),
            AttributeDataType::Enum => $this->validateEnum($attribute, $normalized),
            AttributeDataType::MultiEnum => $this->validateMultiEnum($attribute, $normalized),
            AttributeDataType::Json => $this->validateJson($attribute, $normalized),
        };

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $valueData
     * @return array<string, mixed>
     */
    private function baseValueData(AttributeDefinition $attribute, array $valueData): array
    {
        return [
            'attribute_definition_id' => (int) $attribute->id,
            'value_type' => $attribute->data_type->value,
            'raw_value' => $valueData['raw_value'] ?? null,
            'value_text' => $valueData['value_text'] ?? null,
            'value_number' => $valueData['value_number'] ?? null,
            'value_bool' => $valueData['value_bool'] ?? null,
            'value_enum_code' => $valueData['value_enum_code'] ?? null,
            'value_json' => $valueData['value_json'] ?? null,
            'value_min' => $valueData['value_min'] ?? null,
            'value_max' => $valueData['value_max'] ?? null,
            'source_unit' => $valueData['source_unit'] ?? null,
            'canonical_value' => $valueData['canonical_value'] ?? null,
            'canonical_unit' => $valueData['canonical_unit'] ?? null,
            'confidence' => $valueData['confidence'] ?? null,
            'source_type' => $valueData['source_type'] ?? null,
            'source_id' => $valueData['source_id'] ?? null,
            'source_reference' => $valueData['source_reference'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $valueData
     */
    private function validateNumeric(AttributeDefinition $attribute, array &$valueData): void
    {
        if (filled($valueData['value_text']) || filled($valueData['value_enum_code']) || $valueData['value_bool'] !== null) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] expects a numeric value.");
        }

        if ($valueData['value_number'] !== null && $valueData['value_number'] !== '' && ! is_numeric($valueData['value_number'])) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] value_number must be numeric.");
        }

        if ($attribute->data_type === AttributeDataType::Integer && filled($valueData['value_number']) && ! preg_match('/\A-?\d+\z/', (string) $valueData['value_number'])) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] value_number must be an integer.");
        }
    }

    /**
     * @param  array<string, mixed>  $valueData
     */
    private function validateText(AttributeDefinition $attribute, array &$valueData): void
    {
        if (filled($valueData['value_number']) || filled($valueData['value_enum_code']) || $valueData['value_bool'] !== null) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] expects a text value.");
        }

        if ($valueData['value_text'] !== null && ! is_scalar($valueData['value_text'])) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] value_text must be scalar.");
        }

        $valueData['value_text'] = $valueData['value_text'] === null ? null : (string) $valueData['value_text'];
    }

    /**
     * @param  array<string, mixed>  $valueData
     */
    private function validateBoolean(AttributeDefinition $attribute, array &$valueData): void
    {
        if (filled($valueData['value_text']) || filled($valueData['value_number']) || filled($valueData['value_enum_code'])) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] expects a boolean value.");
        }

        if ($valueData['value_bool'] === '' || $valueData['value_bool'] === null) {
            $valueData['value_bool'] = null;

            return;
        }

        if (in_array($valueData['value_bool'], [true, false, 1, 0, '1', '0'], true)) {
            $valueData['value_bool'] = filter_var($valueData['value_bool'], FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

            return;
        }

        throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] value_bool must be boolean.");
    }

    /**
     * @param  array<string, mixed>  $valueData
     */
    private function validateEnum(AttributeDefinition $attribute, array &$valueData): void
    {
        if (filled($valueData['value_text']) || filled($valueData['value_number']) || $valueData['value_bool'] !== null) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] expects an enum option.");
        }

        if (blank($valueData['value_enum_code'])) {
            $valueData['value_enum_code'] = null;

            return;
        }

        $allowedCodes = $attribute->options->pluck('code')->all();

        if (! in_array($valueData['value_enum_code'], $allowedCodes, true)) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] enum option [{$valueData['value_enum_code']}] is not allowed.");
        }
    }

    /**
     * @param  array<string, mixed>  $valueData
     */
    private function validateMultiEnum(AttributeDefinition $attribute, array &$valueData): void
    {
        if (filled($valueData['value_text']) || filled($valueData['value_number']) || filled($valueData['value_enum_code']) || $valueData['value_bool'] !== null) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] expects multi-enum options.");
        }

        if ($valueData['value_json'] === null || $valueData['value_json'] === '') {
            $valueData['value_json'] = [];

            return;
        }

        if (! is_array($valueData['value_json'])) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] value_json must be an array.");
        }

        $allowedCodes = $attribute->options->pluck('code')->all();

        foreach ($valueData['value_json'] as $optionCode) {
            if (! in_array($optionCode, $allowedCodes, true)) {
                throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] multi-enum option [{$optionCode}] is not allowed.");
            }
        }
    }

    /**
     * @param  array<string, mixed>  $valueData
     */
    private function validateJson(AttributeDefinition $attribute, array &$valueData): void
    {
        if ($valueData['value_json'] !== null && ! is_array($valueData['value_json'])) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] value_json must be an array.");
        }
    }
}

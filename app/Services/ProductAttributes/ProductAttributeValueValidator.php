<?php

namespace App\Services\ProductAttributes;

use App\Enums\AttributeDataType;
use App\Exceptions\ProductAttributes\CannotSaveProductSpecsException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MeasurementUnit;
use Illuminate\Support\Collection;

final class ProductAttributeValueValidator
{
    /**
     * @var array<string, bool>
     */
    private array $unitCompatibilityCache = [];

    /**
     * @param  array<int|string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    public function validate(CentralProduct $product, array $payload): array
    {
        $this->unitCompatibilityCache = [];

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
            if (array_key_exists((int) $attribute->id, $validated)) {
                throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] is referenced more than once.");
            }

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
        $this->validateRawValue($attribute, $normalized);

        match ($attribute->data_type) {
            AttributeDataType::Integer, AttributeDataType::Decimal => $this->validateNumeric($attribute, $normalized),
            AttributeDataType::String, AttributeDataType::Text => $this->validateText($attribute, $normalized),
            AttributeDataType::Boolean => $this->validateBoolean($attribute, $normalized),
            AttributeDataType::Enum => $this->validateEnum($attribute, $normalized),
            AttributeDataType::MultiEnum => $this->validateMultiEnum($attribute, $normalized),
            AttributeDataType::Json => $this->validateJson($attribute, $normalized),
        };

        $this->validateMetadata($attribute, $normalized);

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

        if ($valueData['value_number'] === '') {
            $valueData['value_number'] = null;
        }

        if ($valueData['value_number'] !== null && ! is_numeric($valueData['value_number'])) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] value_number must be numeric.");
        }

        $this->validateRange($attribute, $valueData);

        if ($attribute->data_type === AttributeDataType::Integer && filled($valueData['value_number']) && ! preg_match('/\A-?\d+\z/', (string) $valueData['value_number'])) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] value_number must be an integer.");
        }

        $this->validateNumericUnits($attribute, $valueData);
    }

    /**
     * @param  array<string, mixed>  $valueData
     */
    private function validateNumericUnits(AttributeDefinition $attribute, array &$valueData): void
    {
        if (filled($attribute->canonical_unit) && blank($valueData['canonical_unit'])) {
            $valueData['canonical_unit'] = $attribute->canonical_unit;
        }

        if (filled($attribute->canonical_unit) && filled($valueData['canonical_unit']) && $valueData['canonical_unit'] !== $attribute->canonical_unit) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] canonical_unit must be [{$attribute->canonical_unit}].");
        }

        if (blank($attribute->dimension)) {
            return;
        }

        foreach (['source_unit', 'canonical_unit'] as $unitField) {
            if (blank($valueData[$unitField])) {
                continue;
            }

            if (! is_string($valueData[$unitField])) {
                throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] {$unitField} must be a string.");
            }

            if (! $this->isUnitAllowedForDimension((string) $valueData[$unitField], (string) $attribute->dimension)) {
                throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] {$unitField} [{$valueData[$unitField]}] is not allowed for dimension [{$attribute->dimension}].");
            }
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

        if (! is_string($valueData['value_enum_code']) && ! is_numeric($valueData['value_enum_code'])) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] value_enum_code must be scalar.");
        }

        $valueData['value_enum_code'] = (string) $valueData['value_enum_code'];
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

        foreach ($valueData['value_json'] as $index => $optionCode) {
            if (! is_string($optionCode) && ! is_numeric($optionCode)) {
                throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] multi-enum option at index [{$index}] must be scalar.");
            }

            $optionCode = (string) $optionCode;
            $valueData['value_json'][$index] = $optionCode;

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
        if (filled($valueData['value_number']) || filled($valueData['value_text']) || $valueData['value_bool'] !== null || filled($valueData['value_enum_code'])) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] expects a JSON value.");
        }

        if ($valueData['value_json'] !== null && ! is_array($valueData['value_json'])) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] value_json must be an array.");
        }
    }

    /**
     * @param  array<string, mixed>  $valueData
     */
    private function validateMetadata(AttributeDefinition $attribute, array &$valueData): void
    {
        if ($valueData['confidence'] === '') {
            $valueData['confidence'] = null;
        }

        if ($valueData['confidence'] !== null) {
            if (! is_numeric($valueData['confidence'])) {
                throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] confidence must be numeric.");
            }

            $confidence = (float) $valueData['confidence'];

            if ($confidence < 0 || $confidence > 1) {
                throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] confidence must be between 0 and 1.");
            }

            $valueData['confidence'] = $confidence;
        }

        foreach (['source_type' => 50, 'source_id' => 255] as $field => $maxLength) {
            if ($valueData[$field] === '') {
                $valueData[$field] = null;
            }

            if ($valueData[$field] !== null && (! is_string($valueData[$field]) || mb_strlen($valueData[$field]) > $maxLength)) {
                throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] {$field} must be a string up to {$maxLength} characters.");
            }
        }

        if ($valueData['source_reference'] === '') {
            $valueData['source_reference'] = null;
        }

        if ($valueData['source_reference'] !== null && ! is_array($valueData['source_reference'])) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] source_reference must be an array.");
        }
    }

    /**
     * @param  array<string, mixed>  $valueData
     */
    private function validateRawValue(AttributeDefinition $attribute, array &$valueData): void
    {
        if ($valueData['raw_value'] === '') {
            $valueData['raw_value'] = null;
        }

        if ($valueData['raw_value'] !== null && ! is_scalar($valueData['raw_value'])) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] raw_value must be scalar.");
        }

        $valueData['raw_value'] = $valueData['raw_value'] === null ? null : (string) $valueData['raw_value'];
    }

    /**
     * @param  array<string, mixed>  $valueData
     */
    private function validateRange(AttributeDefinition $attribute, array &$valueData): void
    {
        foreach (['value_min', 'value_max'] as $field) {
            if ($valueData[$field] === '') {
                $valueData[$field] = null;
            }

            if ($valueData[$field] !== null && ! is_numeric($valueData[$field])) {
                throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] {$field} must be numeric.");
            }
        }

        if ($valueData['value_min'] !== null && $valueData['value_max'] !== null && (float) $valueData['value_min'] > (float) $valueData['value_max']) {
            throw CannotSaveProductSpecsException::because("Attribute [{$attribute->code}] value_min must be less than or equal to value_max.");
        }
    }

    private function isUnitAllowedForDimension(string $unitCode, string $dimensionCode): bool
    {
        $cacheKey = "{$dimensionCode}:{$unitCode}";

        if (array_key_exists($cacheKey, $this->unitCompatibilityCache)) {
            return $this->unitCompatibilityCache[$cacheKey];
        }

        return $this->unitCompatibilityCache[$cacheKey] = MeasurementUnit::query()
            ->active()
            ->where('code', $unitCode)
            ->whereHas('dimension', fn ($query) => $query->where('code', $dimensionCode))
            ->exists();
    }
}

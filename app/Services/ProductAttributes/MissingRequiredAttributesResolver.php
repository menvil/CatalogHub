<?php

namespace App\Services\ProductAttributes;

use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;

final class MissingRequiredAttributesResolver
{
    /**
     * @param  array<int, array<string, mixed>>  $state
     * @return list<AttributeDefinition>
     */
    public function resolve(CentralProduct $product, array $state = []): array
    {
        $product->loadMissing([
            'category.attributeDefinitions.section',
            'attributeValues',
        ]);

        if (! $product->category) {
            return [];
        }

        return $product->category->attributeDefinitions
            ->where('is_required', true)
            ->sort(function (AttributeDefinition $first, AttributeDefinition $second): int {
                $firstSection = $first->section;
                $secondSection = $second->section;

                return [
                    $firstSection instanceof AttributeSection ? $firstSection->position : PHP_INT_MAX,
                    $first->position,
                    $first->id,
                ] <=> [
                    $secondSection instanceof AttributeSection ? $secondSection->position : PHP_INT_MAX,
                    $second->position,
                    $second->id,
                ];
            })
            ->reject(function (AttributeDefinition $attribute) use ($product, $state): bool {
                if (array_key_exists($attribute->id, $state)) {
                    return $this->stateHasTypedValue($attribute, $state[$attribute->id]);
                }

                $value = $product->attributeValues->firstWhere('attribute_definition_id', $attribute->id);

                return $value instanceof CentralProductAttributeValue && $this->hasTypedValue($attribute, $value);
            })
            ->values()
            ->all();
    }

    private function hasTypedValue(AttributeDefinition $attribute, CentralProductAttributeValue $value): bool
    {
        $jsonValue = $value->getAttribute('value_json');

        return match ($attribute->data_type) {
            AttributeDataType::Integer, AttributeDataType::Decimal => filled($value->value_number) || filled($value->canonical_value),
            AttributeDataType::String, AttributeDataType::Text => filled($value->value_text),
            AttributeDataType::Boolean => $value->value_bool !== null,
            AttributeDataType::Enum => filled($value->value_enum_code),
            AttributeDataType::MultiEnum, AttributeDataType::Json => is_array($jsonValue) && $jsonValue !== [],
        };
    }

    /**
     * @param  array<string, mixed>  $state
     */
    private function stateHasTypedValue(AttributeDefinition $attribute, array $state): bool
    {
        return match ($attribute->data_type) {
            AttributeDataType::Integer, AttributeDataType::Decimal => filled($state['value_number'] ?? null) || filled($state['canonical_value'] ?? null),
            AttributeDataType::String, AttributeDataType::Text => filled($state['value_text'] ?? null),
            AttributeDataType::Boolean => array_key_exists('value_bool', $state) && $state['value_bool'] !== null && $state['value_bool'] !== '',
            AttributeDataType::Enum => filled($state['value_enum_code'] ?? null),
            AttributeDataType::MultiEnum, AttributeDataType::Json => ! empty($state['value_json'] ?? null) || filled($state['value_json_text'] ?? null),
        };
    }
}

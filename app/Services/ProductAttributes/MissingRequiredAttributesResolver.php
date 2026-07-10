<?php

namespace App\Services\ProductAttributes;

use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;

final class MissingRequiredAttributesResolver
{
    /**
     * @return list<AttributeDefinition>
     */
    public function resolve(CentralProduct $product): array
    {
        $product->loadMissing([
            'category.attributeDefinitions',
            'attributeValues',
        ]);

        if (! $product->category) {
            return [];
        }

        return $product->category->attributeDefinitions
            ->where('is_required', true)
            ->reject(function (AttributeDefinition $attribute) use ($product): bool {
                $value = $product->attributeValues->firstWhere('attribute_definition_id', $attribute->id);

                return $value instanceof CentralProductAttributeValue && $this->hasTypedValue($attribute, $value);
            })
            ->values()
            ->all();
    }

    private function hasTypedValue(AttributeDefinition $attribute, CentralProductAttributeValue $value): bool
    {
        return match ($attribute->data_type) {
            AttributeDataType::Integer, AttributeDataType::Decimal => filled($value->value_number) || filled($value->canonical_value),
            AttributeDataType::String, AttributeDataType::Text => filled($value->value_text),
            AttributeDataType::Boolean => $value->value_bool !== null,
            AttributeDataType::Enum => filled($value->value_enum_code),
            AttributeDataType::MultiEnum, AttributeDataType::Json => is_array($value->value_json) && $value->value_json !== [],
        };
    }
}

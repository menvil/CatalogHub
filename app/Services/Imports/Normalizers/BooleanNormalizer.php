<?php

namespace App\Services\Imports\Normalizers;

use App\Contracts\Imports\AttributeValueNormalizerInterface;
use App\Data\Imports\NormalizedAttributeValueData;
use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use Illuminate\Support\Str;

final class BooleanNormalizer implements AttributeValueNormalizerInterface
{
    /** @var list<string> */
    private const TRUE_ALIASES = ['1', 'true', 'yes', 'y', 'on', 'да', 'есть'];

    /** @var list<string> */
    private const FALSE_ALIASES = ['0', 'false', 'no', 'n', 'off', 'нет', 'отсутствует'];

    public function supports(AttributeDefinition $definition): bool
    {
        return $definition->data_type === AttributeDataType::Boolean;
    }

    public function normalize(
        AttributeDefinition $definition,
        mixed $rawValue,
    ): NormalizedAttributeValueData {
        if (is_bool($rawValue)) {
            return NormalizedAttributeValueData::success($rawValue, $rawValue);
        }

        if ($rawValue === 1 || $rawValue === 0) {
            return NormalizedAttributeValueData::success($rawValue === 1, $rawValue);
        }

        if (is_string($rawValue)) {
            $normalized = Str::lower(trim($rawValue));

            if (in_array($normalized, self::TRUE_ALIASES, true)) {
                return NormalizedAttributeValueData::success(true, $rawValue);
            }

            if (in_array($normalized, self::FALSE_ALIASES, true)) {
                return NormalizedAttributeValueData::success(false, $rawValue);
            }
        }

        return NormalizedAttributeValueData::failure(
            $rawValue,
            'invalid_boolean',
            'The raw value is not a recognized boolean alias.',
        );
    }
}

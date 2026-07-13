<?php

namespace App\Services\Imports;

use App\Contracts\Imports\AttributeValueNormalizerInterface;
use App\Data\Imports\NormalizedAttributeValueData;
use App\Models\CentralCatalog\AttributeDefinition;

final readonly class AttributeNormalizer
{
    /** @param iterable<AttributeValueNormalizerInterface> $normalizers */
    public function __construct(private iterable $normalizers = []) {}

    public function normalize(
        AttributeDefinition $definition,
        mixed $rawValue,
    ): NormalizedAttributeValueData {
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->supports($definition)) {
                return $normalizer->normalize($definition, $rawValue);
            }
        }

        return NormalizedAttributeValueData::failure(
            $rawValue,
            'unsupported_attribute_type',
            "No normalizer supports attribute type [{$definition->data_type->value}].",
            ['data_type' => $definition->data_type->value],
        );
    }
}

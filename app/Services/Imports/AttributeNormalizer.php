<?php

namespace App\Services\Imports;

use App\Contracts\Imports\AttributeValueNormalizerInterface;
use App\Data\Imports\NormalizedAttributeValueData;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Services\AttributeGlobalization\AttributeIdentityReservation;
use Illuminate\Validation\ValidationException;

final readonly class AttributeNormalizer
{
    /** @param iterable<AttributeValueNormalizerInterface> $normalizers */
    public function __construct(private iterable $normalizers = []) {}

    /** @return array<string, mixed> */
    public function candidate(CategoryAttributeAssignment $assignment, mixed $rawValue): array
    {
        $assignment = $assignment->fresh(['definition.options', 'definition.canonicalMeasurementUnit']);
        if ($assignment === null) {
            throw ValidationException::withMessages(['assignment' => 'Import assignment no longer exists.']);
        }
        $definition = $assignment->definition;
        app(AttributeIdentityReservation::class)->assertResolved($definition);
        $normalized = $this->normalize($definition, $rawValue);

        return ['category_attribute_assignment_id' => $assignment->id, 'attribute_definition_id' => $definition->id, 'code' => $definition->code,
            'measurement_dimension_id' => $definition->measurement_dimension_id, 'canonical_measurement_unit_id' => $definition->canonical_measurement_unit_id,
            'value_type' => $definition->data_type->value, 'is_valid' => $normalized->isValid, 'value' => $normalized->value,
            'raw_value' => $normalized->rawValue, 'metadata' => $normalized->metadata, 'error_code' => $normalized->errorCode];
    }

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

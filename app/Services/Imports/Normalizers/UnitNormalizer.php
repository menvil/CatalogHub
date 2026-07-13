<?php

namespace App\Services\Imports\Normalizers;

use App\Contracts\Imports\AttributeValueNormalizerInterface;
use App\Data\Imports\NormalizedAttributeValueData;
use App\Enums\AttributeDataType;
use App\Exceptions\Units\CannotConvertUnitException;
use App\Exceptions\Units\CannotParseUnitException;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Services\Units\UnitConverter;
use App\Services\Units\UnitParser;
use App\Services\Units\UnitResolver;

final readonly class UnitNormalizer implements AttributeValueNormalizerInterface
{
    public function __construct(
        private UnitParser $parser,
        private UnitConverter $converter,
        private UnitResolver $resolver,
    ) {}

    public function supports(AttributeDefinition $definition): bool
    {
        return in_array($definition->data_type, [AttributeDataType::Integer, AttributeDataType::Decimal], true)
            && filled($definition->dimension)
            && filled($definition->canonical_unit);
    }

    public function normalize(
        AttributeDefinition $definition,
        mixed $rawValue,
    ): NormalizedAttributeValueData {
        if (! is_string($rawValue)) {
            return $this->failure($rawValue, 'invalid_unit_value', 'A measured value must contain a number and unit.');
        }

        try {
            $parsed = $this->parser->parse($rawValue);
            $canonicalUnit = $this->resolver->resolve((string) $definition->canonical_unit);

            if ($canonicalUnit->dimension?->code !== $definition->dimension) {
                return $this->failure(
                    $rawValue,
                    'incompatible_unit_dimension',
                    'The canonical unit does not belong to the attribute dimension.'
                );
            }

            $canonicalValue = $this->converter->convert(
                $parsed->value,
                $parsed->unit_code,
                $canonicalUnit,
            );
        } catch (CannotParseUnitException $exception) {
            return $this->failure($rawValue, 'invalid_unit_value', $exception->getMessage());
        } catch (CannotConvertUnitException $exception) {
            return $this->failure($rawValue, 'incompatible_unit_dimension', $exception->getMessage());
        }

        if (! is_finite($canonicalValue)) {
            return $this->failure($rawValue, 'invalid_unit_value', 'The canonical measured value must be finite.');
        }

        if ($definition->data_type === AttributeDataType::Integer) {
            $roundedValue = round($canonicalValue);
            $tolerance = 1e-8;

            if (abs($canonicalValue - $roundedValue) > $tolerance) {
                return $this->failure(
                    $rawValue,
                    'invalid_integer',
                    'The canonical measured value contains a fraction for an integer attribute.'
                );
            }

            $canonicalValue = $roundedValue;
        }

        return NormalizedAttributeValueData::success($canonicalValue, $rawValue, [
            'source_value' => $rawValue,
            'source_numeric_value' => $parsed->value,
            'source_unit' => $parsed->unit_code,
            'canonical_value' => $canonicalValue,
            'canonical_unit' => $canonicalUnit->code,
        ]);
    }

    private function failure(mixed $rawValue, string $code, string $message): NormalizedAttributeValueData
    {
        return NormalizedAttributeValueData::failure($rawValue, $code, $message);
    }
}

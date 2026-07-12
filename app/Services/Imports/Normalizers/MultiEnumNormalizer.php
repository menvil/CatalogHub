<?php

namespace App\Services\Imports\Normalizers;

use App\Contracts\Imports\AttributeValueNormalizerInterface;
use App\Data\Imports\NormalizedAttributeValueData;
use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;

final readonly class MultiEnumNormalizer implements AttributeValueNormalizerInterface
{
    public function __construct(private EnumNormalizer $enumNormalizer) {}

    public function supports(AttributeDefinition $definition): bool
    {
        return $definition->data_type === AttributeDataType::MultiEnum;
    }

    public function normalize(
        AttributeDefinition $definition,
        mixed $rawValue,
    ): NormalizedAttributeValueData {
        $tokens = $this->tokens($rawValue);
        $optionCodes = [];
        $unresolved = [];

        foreach ($tokens as $token) {
            $result = $this->enumNormalizer->normalize($definition, $token);

            if ($result->isValid) {
                $optionCodes[] = (string) $result->value;
            } else {
                $unresolved[] = $token;
            }
        }

        $optionCodes = array_values(array_unique($optionCodes));
        $unresolved = array_values(array_unique($unresolved));

        return new NormalizedAttributeValueData(
            isValid: $unresolved === [],
            value: $optionCodes,
            rawValue: $rawValue,
            errorCode: $unresolved === [] ? null : 'unresolved_enum_options',
            errorMessage: $unresolved === [] ? null : 'One or more enum tokens do not match known options.',
            metadata: ['unresolved_tokens' => $unresolved],
        );
    }

    /** @return list<string> */
    private function tokens(mixed $rawValue): array
    {
        $values = is_array($rawValue) ? $rawValue : [$rawValue];
        $tokens = [];

        foreach ($values as $value) {
            if (! is_scalar($value)) {
                continue;
            }

            $parts = preg_split('/\s*[,\/;]\s*|\s*\+\s*/u', trim((string) $value)) ?: [];

            foreach ($parts as $part) {
                if ($part !== '') {
                    $tokens[] = $part;
                }
            }
        }

        return $tokens;
    }
}

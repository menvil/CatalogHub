<?php

namespace App\Services\Imports\Normalizers;

use App\Contracts\Imports\AttributeValueNormalizerInterface;
use App\Data\Imports\NormalizedAttributeValueData;
use App\Enums\AttributeDataType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

final class EnumNormalizer implements AttributeValueNormalizerInterface
{
    /** @var array<int, Collection<int, AttributeOption>> */
    private array $optionsByDefinitionId = [];

    public function supports(AttributeDefinition $definition): bool
    {
        return $definition->data_type === AttributeDataType::Enum;
    }

    public function normalize(
        AttributeDefinition $definition,
        mixed $rawValue,
    ): NormalizedAttributeValueData {
        if (! is_scalar($rawValue)) {
            return $this->unknown($rawValue);
        }

        $needle = $this->normalizeToken((string) $rawValue);
        $definitionId = (int) $definition->getKey();
        $options = $this->optionsByDefinitionId[$definitionId] ??= $definition->options()
            ->where('is_visible', true)
            ->with('translations')
            ->get();

        foreach ($options as $option) {
            if ($this->normalizeToken($option->code) === $needle) {
                return $this->success($option, $rawValue, 'code');
            }
        }

        foreach ($options as $option) {
            if ($this->normalizeToken($option->label) === $needle) {
                return $this->success($option, $rawValue, 'label');
            }

            foreach ($option->translations as $translation) {
                if (filled($translation->label) && $this->normalizeToken((string) $translation->label) === $needle) {
                    return $this->success($option, $rawValue, 'localized_label');
                }
            }
        }

        return $this->unknown($rawValue);
    }

    private function normalizeToken(string $value): string
    {
        return preg_replace('/[^\pL\pN]+/u', '', Str::lower(trim($value))) ?? '';
    }

    private function success(
        AttributeOption $option,
        mixed $rawValue,
        string $matchedBy,
    ): NormalizedAttributeValueData {
        return NormalizedAttributeValueData::success($option->code, $rawValue, [
            'option_id' => $option->id,
            'option_label' => $option->label,
            'matched_by' => $matchedBy,
        ]);
    }

    private function unknown(mixed $rawValue): NormalizedAttributeValueData
    {
        return NormalizedAttributeValueData::failure(
            $rawValue,
            'unknown_enum_option',
            'The raw value does not match a known enum option.',
        );
    }
}

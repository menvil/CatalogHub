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

        $rawToken = trim((string) $rawValue);
        $needle = $this->normalizeToken($rawToken);
        $definitionId = (int) $definition->getKey();
        $options = $this->optionsByDefinitionId[$definitionId] ??= $definition->options()
            ->where('is_visible', true)
            ->with('translations')
            ->get();

        $exactCode = $options->first(fn (AttributeOption $option): bool => $option->code === $rawToken);

        if ($exactCode !== null) {
            return $this->success($exactCode, $rawValue, 'code');
        }

        $codeMatches = $options->filter(
            fn (AttributeOption $option): bool => $this->normalizeToken($option->code) === $needle,
        );

        if ($codeMatches->count() > 1) {
            return $this->ambiguous($rawValue);
        }

        if ($codeMatches->count() === 1) {
            return $this->success($codeMatches->firstOrFail(), $rawValue, 'code');
        }

        /** @var array<int, array{option: AttributeOption, matched_by: string}> $labelMatches */
        $labelMatches = [];

        foreach ($options as $option) {
            if ($this->normalizeToken($option->label) === $needle) {
                $labelMatches[$option->id] = ['option' => $option, 'matched_by' => 'label'];
            }

            foreach ($option->translations as $translation) {
                if (filled($translation->label) && $this->normalizeToken((string) $translation->label) === $needle) {
                    $labelMatches[$option->id] ??= ['option' => $option, 'matched_by' => 'localized_label'];
                }
            }
        }

        if (count($labelMatches) > 1) {
            return $this->ambiguous($rawValue);
        }

        if ($labelMatches !== []) {
            $match = reset($labelMatches);

            return $this->success($match['option'], $rawValue, $match['matched_by']);
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

    private function ambiguous(mixed $rawValue): NormalizedAttributeValueData
    {
        return NormalizedAttributeValueData::failure(
            $rawValue,
            'ambiguous_enum_option',
            'The normalized raw value matches more than one enum option.',
        );
    }
}

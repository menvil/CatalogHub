<?php

namespace App\Rules\Facets;

use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

final class ValidFacetDefinitionRule implements DataAwareRule, ValidationRule
{
    /** @var array<string, mixed> */
    private array $data = [];

    /** @param array<string, mixed> $data */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $facetType = is_string($value) ? FacetType::tryFrom($value) : null;
        $sourceValue = data_get($this->data, 'source_type', data_get($this->data, 'data.source_type'));
        $sourceType = is_string($sourceValue) ? FacetSourceType::tryFrom($sourceValue) : null;

        if ($facetType === null || $sourceType === null) {
            $fail('The facet type and source type must be valid.');

            return;
        }

        if ($facetType === FacetType::Checkbox
            && ! in_array($sourceType, [FacetSourceType::Attribute, FacetSourceType::Brand], true)) {
            $fail('Checkbox facets require an attribute or brand source.');
        }
    }
}

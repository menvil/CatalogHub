<?php

namespace App\Rules\Facets;

use App\Enums\AttributeDataType;
use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use App\Models\CentralCatalog\AttributeDefinition;
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

        if ($facetType === FacetType::Range) {
            $this->validateRange($sourceType, $fail);
        }
    }

    private function validateRange(FacetSourceType $sourceType, Closure $fail): void
    {
        if ($sourceType === FacetSourceType::Rating) {
            return;
        }

        if ($sourceType !== FacetSourceType::Attribute) {
            $fail('Range facets require a numeric attribute or rating source.');

            return;
        }

        $attributeId = $this->value('attribute_definition_id');
        $categoryId = $this->value('category_id');
        $attribute = AttributeDefinition::query()
            ->when(filled($categoryId), fn ($query) => $query->where('central_category_id', $categoryId))
            ->find($attributeId);

        if ($attribute === null
            || ! in_array($attribute->data_type, [AttributeDataType::Integer, AttributeDataType::Decimal], true)) {
            $fail('Range facets require an integer or decimal attribute from the selected category.');
        }
    }

    private function value(string $key): mixed
    {
        return data_get($this->data, $key, data_get($this->data, "data.{$key}"));
    }
}

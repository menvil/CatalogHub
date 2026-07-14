<?php

namespace App\Data\Facets;

use App\Enums\AttributeDataType;
use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use App\Models\FacetDefinition;
use Illuminate\Support\Str;

final readonly class FacetDefinitionData
{
    /**
     * @param  array<string, mixed>  $config
     * @param  list<FacetOptionData>  $options
     */
    public function __construct(
        public int $id,
        public string $code,
        public string $label,
        public FacetType $type,
        public FacetSourceType $sourceType,
        public int $position,
        public bool $isCollapsible,
        public bool $defaultCollapsed,
        public array $config = [],
        public array $options = [],
        public ?string $attributeCode = null,
        public ?AttributeDataType $attributeDataType = null,
        public ?string $canonicalUnit = null,
    ) {}

    public static function fromModel(FacetDefinition $facet): self
    {
        return new self(
            id: $facet->id,
            code: $facet->code,
            label: $facet->label_override
                ?: $facet->attributeDefinition?->name
                ?: Str::headline($facet->code),
            type: $facet->facet_type,
            sourceType: $facet->source_type,
            position: $facet->position,
            isCollapsible: $facet->is_collapsible,
            defaultCollapsed: $facet->default_collapsed,
            config: $facet->config_json ?? [],
            options: ($facet->relationLoaded('activeOptions') ? $facet->activeOptions : $facet->options)
                ->map(fn ($option): FacetOptionData => FacetOptionData::fromModel($option))
                ->values()
                ->all(),
            attributeCode: $facet->attributeDefinition?->code,
            attributeDataType: $facet->attributeDefinition?->data_type,
            canonicalUnit: $facet->attributeDefinition?->canonical_unit,
        );
    }
}

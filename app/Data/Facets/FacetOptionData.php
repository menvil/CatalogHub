<?php

namespace App\Data\Facets;

use App\Models\FacetOption;
use Illuminate\Support\Str;

final readonly class FacetOptionData
{
    /** @param array<string, mixed> $config */
    public function __construct(
        public int $id,
        public string $value,
        public string $label,
        public int $position,
        public array $config = [],
        public ?int $count = null,
    ) {}

    public static function fromModel(FacetOption $option): self
    {
        return new self(
            id: $option->id,
            value: $option->value,
            label: $option->label_override ?: Str::headline($option->value),
            position: $option->position,
            config: $option->config_json ?? [],
        );
    }
}

<?php

namespace App\Services\Translations;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MeasurementUnit;

final class TranslationSourceHashService
{
    public function forProduct(CentralProduct $product): string
    {
        return $this->hash([
            'name' => $product->name,
            'model' => $product->model,
        ]);
    }

    public function forCategory(CentralCategory $category): string
    {
        return $this->hash([
            'name' => $category->name,
            'slug' => $category->slug,
        ]);
    }

    public function forAttribute(AttributeDefinition $attribute): string
    {
        return $this->hash([
            'code' => $attribute->code,
            'name' => $attribute->name,
            'data_type' => $attribute->data_type?->value ?? $attribute->data_type,
        ]);
    }

    public function forAttributeSection(AttributeSection $section): string
    {
        return $this->hash([
            'code' => $section->code,
            'name' => $section->name,
        ]);
    }

    public function forAttributeOption(AttributeOption $option): string
    {
        return $this->hash([
            'code' => $option->code,
            'label' => $option->label,
        ]);
    }

    public function forUnit(MeasurementUnit $unit): string
    {
        return $this->hash([
            'code' => $unit->code,
            'symbol' => $unit->symbol,
            'name' => $unit->name,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function hash(array $payload): string
    {
        ksort($payload);

        $normalized = array_map(function (mixed $value): mixed {
            if (is_string($value)) {
                $value = trim($value);

                return $value === '' ? null : $value;
            }

            return $value;
        }, $payload);

        return hash('sha256', (string) json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }
}

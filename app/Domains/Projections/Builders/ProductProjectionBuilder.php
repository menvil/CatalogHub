<?php

namespace App\Domains\Projections\Builders;

use App\Domains\Projections\DTO\ProductProjectionData;
use App\Enums\AttributeDataType;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\Site;

final class ProductProjectionBuilder
{
    public function build(Site $site, CentralProduct $product, string $locale): ProductProjectionData
    {
        $product->loadMissing(['brand', 'category']);

        $title = (string) $product->getAttribute('name');
        $slug = (string) $product->getAttribute('slug');
        $status = $product->status === CentralProductStatus::Active ? 'active' : 'pending';
        $payload = [
            'product' => [
                'id' => (int) $product->getKey(),
                'title' => $title,
                'slug' => $slug,
                'model' => $product->getAttribute('model'),
                'status' => $product->status->value,
            ],
            'brand' => $product->brand === null ? null : [
                'id' => (int) $product->brand->getKey(),
                'name' => (string) $product->brand->getAttribute('name'),
                'slug' => (string) $product->brand->getAttribute('slug'),
            ],
            'category' => $product->category === null ? null : [
                'id' => (int) $product->category->getKey(),
                'name' => (string) $product->category->getAttribute('name'),
                'slug' => (string) $product->category->getAttribute('slug'),
            ],
            'site' => [
                'id' => (int) $site->getKey(),
                'code' => (string) $site->getAttribute('code'),
                'locale' => $locale,
            ],
            'spec_sections' => $this->buildSpecSections($product),
        ];
        $seo = [];
        $media = [];

        return new ProductProjectionData(
            siteId: (int) $site->getKey(),
            locale: $locale,
            centralProductId: (int) $product->getKey(),
            slug: $slug,
            title: $title,
            status: $status,
            payload: $payload,
            seo: $seo,
            media: $media,
            checksum: $this->checksumFor($status, $payload, $seo, $media),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $seo
     * @param  array<string, mixed>  $media
     */
    private function checksumFor(string $status, array $payload, array $seo, array $media): string
    {
        return hash('sha256', json_encode(
            compact('status', 'payload', 'seo', 'media'),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildSpecSections(CentralProduct $product): array
    {
        if ($product->category === null) {
            return [];
        }

        $sections = AttributeSection::query()
            ->where('central_category_id', $product->category->getKey())
            ->where('is_visible', true)
            ->with(['attributes' => fn ($query) => $query->visible()->ordered()])
            ->ordered()
            ->get();
        $values = CentralProductAttributeValue::query()
            ->where('central_product_id', $product->getKey())
            ->get()
            ->keyBy('attribute_definition_id');
        $payload = [];

        foreach ($sections as $section) {
            $attributes = [];

            foreach ($section->attributes as $attribute) {
                $value = $values->get($attribute->getKey());

                if (! $value instanceof CentralProductAttributeValue) {
                    continue;
                }

                $attributes[] = [
                    'code' => (string) $attribute->getAttribute('code'),
                    'label' => (string) $attribute->getAttribute('name'),
                    'data_type' => $attribute->data_type->value,
                    'canonical_value' => $this->canonicalValue($attribute, $value),
                    'canonical_unit' => $value->getAttribute('canonical_unit')
                        ?: $attribute->getAttribute('canonical_unit'),
                    'display_value' => null,
                    'position' => (int) $attribute->getAttribute('position'),
                    'is_filterable' => (bool) $attribute->getAttribute('is_filterable'),
                    'is_sortable' => (bool) $attribute->getAttribute('is_sortable'),
                    'is_comparable' => (bool) $attribute->getAttribute('is_comparable'),
                    'is_searchable' => (bool) $attribute->getAttribute('is_searchable'),
                ];
            }

            if ($attributes === []) {
                continue;
            }

            $payload[] = [
                'code' => (string) $section->getAttribute('code'),
                'label' => (string) $section->getAttribute('name'),
                'position' => (int) $section->getAttribute('position'),
                'attributes' => $attributes,
            ];
        }

        return $payload;
    }

    private function canonicalValue(
        AttributeDefinition $attribute,
        CentralProductAttributeValue $value,
    ): mixed {
        return match ($attribute->data_type) {
            AttributeDataType::Integer, AttributeDataType::Decimal => $this->normalizeNumeric(
                $value->getAttribute('canonical_value') ?? $value->getAttribute('value_number'),
            ),
            AttributeDataType::String, AttributeDataType::Text => $value->getAttribute('value_text'),
            AttributeDataType::Boolean => $value->getAttribute('value_bool'),
            AttributeDataType::Enum => $value->getAttribute('value_enum_code'),
            AttributeDataType::MultiEnum, AttributeDataType::Json => $value->getAttribute('value_json'),
        };
    }

    private function normalizeNumeric(mixed $value): int|float|null
    {
        if (! is_numeric($value)) {
            return null;
        }

        $numeric = (float) $value;

        return fmod($numeric, 1.0) === 0.0 ? (int) $numeric : $numeric;
    }
}

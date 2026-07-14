<?php

namespace App\Domains\Projections\Builders;

use App\Domains\Projections\DTO\ProductProjectionData;
use App\Enums\AttributeDataType;
use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\MeasurementUnit;
use App\Models\Site;
use App\Services\Translations\TranslationResolver;
use Illuminate\Database\Eloquent\Model;

final class ProductProjectionBuilder
{
    public function __construct(
        private readonly TranslationResolver $translationResolver,
    ) {}

    public function build(Site $site, CentralProduct $product, string $locale): ProductProjectionData
    {
        $product->loadMissing(['brand', 'category']);

        $sourceTitle = (string) $product->getAttribute('name');
        $title = $this->translatedString($product, 'name', $locale, $sourceTitle);
        $slug = (string) $product->getAttribute('slug');
        $status = $product->status === CentralProductStatus::Active ? 'active' : 'pending';
        $payload = [
            'product' => [
                'id' => (int) $product->getKey(),
                'title' => $title,
                'source_title' => $sourceTitle,
                'slug' => $slug,
                'model' => $product->getAttribute('model'),
                'status' => $product->status->value,
                'subtitle' => $this->translatedString($product, 'subtitle', $locale),
                'short_description' => $this->translatedString($product, 'short_description', $locale),
                'description' => $this->translatedString($product, 'description', $locale),
            ],
            'brand' => $product->brand === null ? null : [
                'id' => (int) $product->brand->getKey(),
                'name' => (string) $product->brand->getAttribute('name'),
                'slug' => (string) $product->brand->getAttribute('slug'),
            ],
            'category' => $product->category === null ? null : [
                'id' => (int) $product->category->getKey(),
                'name' => (string) $product->category->getAttribute('name'),
                'label' => $this->translatedString(
                    $product->category,
                    'name',
                    $locale,
                    (string) $product->category->getAttribute('name'),
                ),
                'slug' => (string) $product->category->getAttribute('slug'),
                'description' => $this->translatedString($product->category, 'description', $locale),
            ],
            'site' => [
                'id' => (int) $site->getKey(),
                'code' => (string) $site->getAttribute('code'),
                'locale' => $locale,
            ],
            'spec_sections' => $this->buildSpecSections($product, $locale),
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
    private function buildSpecSections(CentralProduct $product, string $locale): array
    {
        if ($product->category === null) {
            return [];
        }

        $sections = AttributeSection::query()
            ->where('central_category_id', $product->category->getKey())
            ->where('is_visible', true)
            ->with([
                'attributes' => fn ($query) => $query->visible()->ordered(),
                'attributes.options' => fn ($query) => $query->where('is_visible', true)->ordered(),
            ])
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

                $canonicalUnit = $value->getAttribute('canonical_unit')
                    ?: $attribute->getAttribute('canonical_unit');
                $attributes[] = [
                    'code' => (string) $attribute->getAttribute('code'),
                    'label' => $this->translatedString(
                        $attribute,
                        'label',
                        $locale,
                        (string) $attribute->getAttribute('name'),
                    ),
                    'data_type' => $attribute->data_type->value,
                    'canonical_value' => $this->canonicalValue($attribute, $value),
                    'canonical_unit' => $canonicalUnit,
                    'canonical_unit_label' => $this->unitLabel($canonicalUnit, $locale),
                    'display_value' => null,
                    'options' => $attribute->options
                        ->map(fn ($option): array => [
                            'code' => (string) $option->getAttribute('code'),
                            'label' => $this->translatedString(
                                $option,
                                'label',
                                $locale,
                                (string) $option->getAttribute('label'),
                            ),
                        ])
                        ->values()
                        ->all(),
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
                'label' => $this->translatedString(
                    $section,
                    'name',
                    $locale,
                    (string) $section->getAttribute('name'),
                ),
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

    private function translatedString(
        Model $entity,
        string $field,
        string $locale,
        ?string $fallback = null,
    ): ?string {
        $value = $this->translationResolver->resolve($entity, $field, $locale)->value;

        return is_scalar($value) ? (string) $value : $fallback;
    }

    private function unitLabel(mixed $unitCode, string $locale): ?string
    {
        if (! is_string($unitCode) || $unitCode === '') {
            return null;
        }

        $unit = MeasurementUnit::query()->where('code', $unitCode)->first();

        if (! $unit instanceof MeasurementUnit) {
            return $unitCode;
        }

        return $this->translatedString(
            $unit,
            'short_name',
            $locale,
            (string) ($unit->getAttribute('symbol') ?: $unit->getAttribute('name') ?: $unitCode),
        );
    }
}

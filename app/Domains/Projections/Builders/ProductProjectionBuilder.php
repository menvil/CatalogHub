<?php

namespace App\Domains\Projections\Builders;

use App\Domains\Projections\DTO\ProductProjectionData;
use App\Enums\AttributeDataType;
use App\Enums\CentralProductStatus;
use App\Exceptions\Units\CannotConvertUnitException;
use App\Models\AttributeDisplayRule;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\MarketUnitPreference;
use App\Models\MeasurementUnit;
use App\Models\MediaAsset;
use App\Models\Site;
use App\Services\Media\MediaResolver;
use App\Services\Media\MediaUrlGenerator;
use App\Services\Translations\TranslationResolver;
use App\Services\Units\UnitConverter;
use App\Services\Units\UnitFormatter;
use Illuminate\Database\Eloquent\Model;

final class ProductProjectionBuilder
{
    public function __construct(
        private readonly TranslationResolver $translationResolver,
        private readonly UnitConverter $unitConverter,
        private readonly UnitFormatter $unitFormatter,
        private readonly MediaResolver $mediaResolver,
        private readonly MediaUrlGenerator $mediaUrlGenerator,
    ) {}

    public function build(Site $site, CentralProduct $product, string $locale): ProductProjectionData
    {
        $product->loadMissing(['brand', 'category']);
        $site->loadMissing('market');

        $sourceTitle = (string) $product->getAttribute('name');
        $title = $this->translatedString($product, 'name', $locale, $sourceTitle);
        $slug = (string) $product->getAttribute('slug');
        $status = $product->status === CentralProductStatus::Active ? 'active' : 'pending';
        $media = $this->buildMediaPayload($site, $product, $locale, $title);
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
            'spec_sections' => $this->buildSpecSections($product, $site, $locale),
            'media' => $media,
        ];
        $seo = [];

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
    private function buildSpecSections(CentralProduct $product, Site $site, string $locale): array
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
                $canonicalValue = $this->canonicalValue($attribute, $value);
                $display = $this->displayValue(
                    $attribute,
                    $canonicalValue,
                    $canonicalUnit,
                    $site,
                    $locale,
                );
                $attributes[] = [
                    'code' => (string) $attribute->getAttribute('code'),
                    'label' => $this->translatedString(
                        $attribute,
                        'label',
                        $locale,
                        (string) $attribute->getAttribute('name'),
                    ),
                    'data_type' => $attribute->data_type->value,
                    'canonical_value' => $canonicalValue,
                    'canonical_unit' => $canonicalUnit,
                    'canonical_unit_label' => $this->unitLabel($canonicalUnit, $locale),
                    'display_value' => $display['value'],
                    'display_unit' => $display['unit'],
                    'display_unit_label' => $this->unitLabel($display['unit'], $locale),
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

    /**
     * @return array{value: ?string, unit: ?string}
     */
    private function displayValue(
        AttributeDefinition $attribute,
        mixed $canonicalValue,
        mixed $canonicalUnit,
        Site $site,
        string $locale,
    ): array {
        $unitCode = is_string($canonicalUnit) && $canonicalUnit !== '' ? $canonicalUnit : null;

        if (
            ! in_array($attribute->data_type, [AttributeDataType::Integer, AttributeDataType::Decimal], true)
            || ! is_numeric($canonicalValue)
        ) {
            return ['value' => null, 'unit' => $unitCode];
        }

        $rule = $this->displayRule($attribute, $site, $locale);
        $sourceUnit = $unitCode === null
            ? null
            : MeasurementUnit::query()->where('code', $unitCode)->active()->first();
        $displayUnit = $rule?->displayUnit()->first()
            ?? $this->preferredMarketUnit($sourceUnit, $site)
            ?? $sourceUnit;

        if (! $displayUnit instanceof MeasurementUnit || $unitCode === null) {
            return [
                'value' => $this->rawDisplayValue($canonicalValue, $unitCode, $locale),
                'unit' => $unitCode,
            ];
        }

        try {
            $value = $displayUnit->getAttribute('code') === $unitCode
                ? $canonicalValue
                : $this->unitConverter->convert($canonicalValue, $unitCode, $displayUnit);

            return [
                'value' => $this->unitFormatter->format(
                    $value,
                    $displayUnit,
                    $rule?->getAttribute('decimals'),
                    $locale,
                ),
                'unit' => (string) $displayUnit->getAttribute('code'),
            ];
        } catch (CannotConvertUnitException) {
            return [
                'value' => $this->rawDisplayValue($canonicalValue, $unitCode, $locale),
                'unit' => $unitCode,
            ];
        }
    }

    private function displayRule(
        AttributeDefinition $attribute,
        Site $site,
        string $locale,
    ): ?AttributeDisplayRule {
        $marketCode = $site->market === null
            ? AttributeDisplayRule::GLOBAL_MARKET_CODE
            : (string) $site->market->getAttribute('code');
        $rule = AttributeDisplayRule::query()
            ->where('attribute_definition_id', $attribute->getKey())
            ->whereIn('market_code', array_unique([$marketCode, AttributeDisplayRule::GLOBAL_MARKET_CODE]))
            ->whereIn('locale', [$locale, AttributeDisplayRule::GLOBAL_LOCALE])
            ->get()
            ->sortByDesc(fn (AttributeDisplayRule $candidate): int => ($candidate->getAttribute('market_code') === $marketCode ? 2 : 0)
                + ($candidate->getAttribute('locale') === $locale ? 1 : 0))
            ->first();

        return $rule instanceof AttributeDisplayRule ? $rule : null;
    }

    private function preferredMarketUnit(?MeasurementUnit $sourceUnit, Site $site): ?MeasurementUnit
    {
        if (! $sourceUnit instanceof MeasurementUnit || $site->market === null) {
            return null;
        }

        $preference = MarketUnitPreference::query()
            ->where('market_code', $site->market->getAttribute('code'))
            ->where('dimension_id', $sourceUnit->getAttribute('dimension_id'))
            ->first();

        return $preference?->preferredUnit()->first();
    }

    private function rawDisplayValue(mixed $value, ?string $unitCode, string $locale): string
    {
        $number = (string) $value;
        $language = mb_strtolower(str($locale)->before('_')->before('-')->toString());

        if (in_array($language, ['bg', 'de', 'fr', 'es', 'it'], true)) {
            $number = str_replace('.', ',', $number);
        }

        return trim($number.' '.($unitCode ?? ''));
    }

    /**
     * @return array<string, mixed>
     */
    private function buildMediaPayload(
        Site $site,
        CentralProduct $product,
        string $locale,
        string $alt,
    ): array {
        $main = $this->resolvedMediaItem($site, $product, 'main', $locale, $alt);
        $gallery = $this->resolvedMediaItem($site, $product, 'gallery', $locale, $alt, false);

        return [
            'main' => $main,
            'gallery' => $gallery === null ? [] : [$gallery],
            'hero' => $this->resolvedMediaItem($site, $product, 'hero', $locale, $alt),
            'og' => $this->resolvedMediaItem($site, $product, 'og', $locale, $alt),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function resolvedMediaItem(
        Site $site,
        CentralProduct $product,
        string $role,
        string $locale,
        string $alt,
        bool $includePlaceholder = true,
    ): ?array {
        $resolution = $this->mediaResolver->explain(
            entityType: 'central_product',
            entityId: (int) $product->getKey(),
            role: $role,
            locale: $locale,
            siteId: (int) $site->getKey(),
            marketId: (int) $site->getAttribute('market_id'),
        );

        if ($resolution->asset instanceof MediaAsset) {
            return $this->mediaAssetPayload($resolution->asset, $alt);
        }

        if (! $includePlaceholder) {
            return null;
        }

        return [
            'asset_id' => null,
            'url' => $resolution->placeholderUrl,
            'alt' => $alt,
            'width' => null,
            'height' => null,
            'mime_type' => null,
            'is_placeholder' => true,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mediaAssetPayload(MediaAsset $asset, string $alt): array
    {
        return [
            'asset_id' => (int) $asset->getKey(),
            'url' => $this->mediaUrlGenerator->forAsset($asset),
            'alt' => $alt,
            'width' => $asset->getAttribute('width'),
            'height' => $asset->getAttribute('height'),
            'mime_type' => $asset->getAttribute('mime_type'),
            'is_placeholder' => false,
        ];
    }
}

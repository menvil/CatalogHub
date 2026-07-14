<?php

namespace App\Domains\Projections\Builders;

use App\Domains\Projections\DTO\ProductProjectionData;
use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\Projections\Support\ProjectionVisibility;
use App\Domains\Seo\SeoProjectionBuilder;
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
use App\Services\Sites\SiteOverrideResolver;
use App\Services\Translations\TranslationResolver;
use App\Services\Units\UnitConverter;
use App\Services\Units\UnitFormatter;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

final class ProductProjectionBuilder
{
    /** @var array<string, MeasurementUnit|null> */
    private array $measurementUnits = [];

    /** @var array<string, AttributeDisplayRule|null> */
    private array $displayRules = [];

    /** @var array<string, MarketUnitPreference|null> */
    private array $marketUnitPreferences = [];

    public function __construct(
        private readonly TranslationResolver $translationResolver,
        private readonly UnitConverter $unitConverter,
        private readonly UnitFormatter $unitFormatter,
        private readonly MediaResolver $mediaResolver,
        private readonly MediaUrlGenerator $mediaUrlGenerator,
        private readonly SiteOverrideResolver $siteOverrideResolver,
        private readonly SeoProjectionBuilder $seoProjectionBuilder,
    ) {}

    public function build(Site $site, CentralProduct $product, string $locale): ProductProjectionData
    {
        $product->loadMissing(['brand', 'category']);
        $site->loadMissing('market');

        $sourceTitle = (string) $product->getAttribute('name');
        $translatedTitle = $this->translatedString($product, 'name', $locale, $sourceTitle);
        $title = $this->overrideString(
            $site,
            $product,
            ['title', 'local_title'],
            $locale,
            $translatedTitle,
        );
        $slug = $this->overrideString(
            $site,
            $product,
            ['slug', 'local_slug'],
            $locale,
            (string) $product->getAttribute('slug'),
        );
        $introText = $this->overrideString($site, $product, ['intro_text'], $locale);
        $heroText = $this->overrideString($site, $product, ['hero_text'], $locale);
        $visibility = $this->siteOverrideResolver->resolve(
            $site,
            'product',
            (int) $product->getKey(),
            'visibility',
            $locale,
            fallbackValue: 'visible',
        );
        $status = $product->status === CentralProductStatus::Active && ProjectionVisibility::isVisible($visibility)
            ? ProjectionStatus::Active
            : ProjectionStatus::Pending;
        $media = $this->buildMediaPayload($site, $product, $locale, $title);
        $payload = [
            'product' => [
                'id' => (int) $product->getKey(),
                'title' => $title,
                'source_title' => $sourceTitle,
                'slug' => $slug,
                'model' => $product->getAttribute('model'),
                'status' => $product->status->value,
                'visibility' => $visibility,
                'subtitle' => $this->translatedString($product, 'subtitle', $locale),
                'short_description' => $this->translatedString($product, 'short_description', $locale),
                'description' => $this->translatedString($product, 'description', $locale),
                'intro_text' => $introText,
                'hero_text' => $heroText,
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
        $seo = $this->seoProjectionBuilder->forProduct(
            $site,
            $product,
            $locale,
            $title,
            $slug,
            $status === ProjectionStatus::Active,
            $media,
        );
        $payload['seo'] = $seo;

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
            builtAt: $product->getAttribute('updated_at') instanceof DateTimeInterface
                ? CarbonImmutable::instance($product->getAttribute('updated_at'))
                : null,
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $seo
     * @param  array<string, mixed>  $media
     */
    private function checksumFor(
        ProjectionStatus $status,
        array $payload,
        array $seo,
        array $media,
    ): string {
        return hash('sha256', json_encode([
            'status' => $status->value,
            'payload' => $payload,
            'seo' => $seo,
            'media' => $media,
        ],
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
                $sourceUnit = is_string($canonicalUnit) && $canonicalUnit !== ''
                    ? $this->measurementUnit($canonicalUnit)
                    : null;
                $display = $this->displayValue(
                    $attribute,
                    $canonicalValue,
                    $canonicalUnit,
                    $site,
                    $locale,
                    $sourceUnit,
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
                    'canonical_unit_label' => $this->unitLabel($canonicalUnit, $locale, $sourceUnit),
                    'display_value' => $display['value'],
                    'display_unit' => $display['unit'],
                    'display_unit_label' => $this->unitLabel(
                        $display['unit'],
                        $locale,
                        $display['unit_model'],
                    ),
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

    private function unitLabel(
        mixed $unitCode,
        string $locale,
        ?MeasurementUnit $unit = null,
    ): ?string {
        if (! is_string($unitCode) || $unitCode === '') {
            return null;
        }

        $unit ??= $this->measurementUnit($unitCode);

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
     * @return array{value: ?string, unit: ?string, unit_model: ?MeasurementUnit}
     */
    private function displayValue(
        AttributeDefinition $attribute,
        mixed $canonicalValue,
        mixed $canonicalUnit,
        Site $site,
        string $locale,
        ?MeasurementUnit $sourceUnit,
    ): array {
        $unitCode = is_string($canonicalUnit) && $canonicalUnit !== '' ? $canonicalUnit : null;

        if (
            ! in_array($attribute->data_type, [AttributeDataType::Integer, AttributeDataType::Decimal], true)
            || ! is_numeric($canonicalValue)
        ) {
            return ['value' => null, 'unit' => $unitCode, 'unit_model' => $sourceUnit];
        }

        $rule = $this->displayRule($attribute, $site, $locale);
        $activeSourceUnit = $sourceUnit instanceof MeasurementUnit
            && (bool) $sourceUnit->getAttribute('is_active')
                ? $sourceUnit
                : null;
        $displayUnit = $rule instanceof AttributeDisplayRule ? $rule->displayUnit : null;
        $displayUnit ??= $this->preferredMarketUnit($activeSourceUnit, $site) ?? $activeSourceUnit;

        if (! $displayUnit instanceof MeasurementUnit || $unitCode === null) {
            return [
                'value' => $this->rawDisplayValue($canonicalValue, $unitCode, $locale),
                'unit' => $unitCode,
                'unit_model' => $sourceUnit,
            ];
        }

        $requiresConversion = $displayUnit->getAttribute('code') !== $unitCode;

        if ($requiresConversion && ! $activeSourceUnit instanceof MeasurementUnit) {
            return [
                'value' => $this->rawDisplayValue($canonicalValue, $unitCode, $locale),
                'unit' => $unitCode,
                'unit_model' => $sourceUnit,
            ];
        }

        try {
            $value = $requiresConversion
                ? $this->unitConverter->convert($canonicalValue, $activeSourceUnit, $displayUnit)
                : $canonicalValue;

            return [
                'value' => $this->unitFormatter->format(
                    $value,
                    $displayUnit,
                    $rule?->getAttribute('decimals'),
                    $locale,
                ),
                'unit' => (string) $displayUnit->getAttribute('code'),
                'unit_model' => $displayUnit,
            ];
        } catch (CannotConvertUnitException) {
            return [
                'value' => $this->rawDisplayValue($canonicalValue, $unitCode, $locale),
                'unit' => $unitCode,
                'unit_model' => $sourceUnit,
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
        $cacheKey = implode('|', [(string) $attribute->getKey(), $marketCode, $locale]);

        if (array_key_exists($cacheKey, $this->displayRules)) {
            return $this->displayRules[$cacheKey];
        }

        $rule = AttributeDisplayRule::query()
            ->with('displayUnit')
            ->where('attribute_definition_id', $attribute->getKey())
            ->whereIn('market_code', array_unique([$marketCode, AttributeDisplayRule::GLOBAL_MARKET_CODE]))
            ->whereIn('locale', [$locale, AttributeDisplayRule::GLOBAL_LOCALE])
            ->get()
            ->sortByDesc(fn (AttributeDisplayRule $candidate): int => ($candidate->getAttribute('market_code') === $marketCode ? 2 : 0)
                + ($candidate->getAttribute('locale') === $locale ? 1 : 0))
            ->first();

        return $this->displayRules[$cacheKey] = $rule instanceof AttributeDisplayRule ? $rule : null;
    }

    private function preferredMarketUnit(?MeasurementUnit $sourceUnit, Site $site): ?MeasurementUnit
    {
        if (! $sourceUnit instanceof MeasurementUnit || $site->market === null) {
            return null;
        }

        $marketCode = (string) $site->market->getAttribute('code');
        $dimensionId = (int) $sourceUnit->getAttribute('dimension_id');
        $cacheKey = $marketCode.'|'.$dimensionId;

        if (array_key_exists($cacheKey, $this->marketUnitPreferences)) {
            return $this->marketUnitPreferences[$cacheKey]?->preferredUnit;
        }

        $preference = MarketUnitPreference::query()
            ->with('preferredUnit')
            ->where('market_code', $marketCode)
            ->where('dimension_id', $dimensionId)
            ->first();

        $this->marketUnitPreferences[$cacheKey] = $preference;

        return $preference?->preferredUnit;
    }

    private function rawDisplayValue(mixed $value, ?string $unitCode, string $locale): string
    {
        $number = (string) $value;
        $language = mb_strtolower(str($locale)->before('_')->before('-')->toString());

        if (in_array($language, ['bg', 'de', 'es', 'fr', 'it', 'nl', 'pl', 'pt', 'ru', 'sv'], true)) {
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

    /**
     * @param  list<string>  $fields
     */
    private function overrideString(
        Site $site,
        CentralProduct $product,
        array $fields,
        string $locale,
        ?string $fallback = null,
    ): ?string {
        $value = $fallback;

        foreach ($fields as $field) {
            $resolved = $this->siteOverrideResolver->resolve(
                $site,
                'product',
                (int) $product->getKey(),
                $field,
                $locale,
                fallbackValue: $value,
            );
            $value = is_scalar($resolved) ? (string) $resolved : $value;
        }

        return $value;
    }

    private function measurementUnit(string $unitCode): ?MeasurementUnit
    {
        if (! array_key_exists($unitCode, $this->measurementUnits)) {
            $this->measurementUnits[$unitCode] = MeasurementUnit::query()
                ->where('code', $unitCode)
                ->first();
        }

        return $this->measurementUnits[$unitCode];
    }
}

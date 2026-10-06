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
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CategoryComparisonAttribute;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\FacetDefinition;
use App\Models\MarketUnitPreference;
use App\Models\MeasurementUnit;
use App\Models\MediaAsset;
use App\Models\Review;
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
        $attributes = $this->buildAttributes($product, $site, $locale);
        $rating = Review::query()->visiblePublicly()->forSite($site)->forProduct($product)->avg('rating');
        $payload = [
            'schema_version' => 1, 'rating' => ['value' => $rating === null ? null : (float) $rating],
            'schema_revision' => $product->category === null ? 0 : $product->category->schema_revision,
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
            'attributes' => $attributes,
            'spec_sections' => $this->specSections($attributes),
            'facet_values' => $this->facetValues($product, $attributes, $rating === null ? null : (float) $rating),
            'comparison' => $this->comparisonRows($product),
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
    private function buildAttributes(CentralProduct $product, Site $site, string $locale): array
    {
        if ($product->category === null) {
            return [];
        }
        $assignments = CategoryAttributeAssignment::query()
            ->where('central_category_id', $product->central_category_id)
            ->with(['section', 'definition.canonicalMeasurementUnit', 'definition.options' => fn ($query) => $query->ordered()])
            ->ordered()->get()->sortBy(fn ($row) => [$row->section === null ? PHP_INT_MAX : $row->section->position, $row->position, $row->id]);
        $values = CentralProductAttributeValue::query()->where('central_product_id', $product->id)->get()->keyBy('attribute_definition_id');
        $payload = [];
        foreach ($assignments as $assignment) {
            $attribute = $assignment->definition;
            $value = $values->get($attribute->id);
            $canonicalValue = $value === null ? null : $this->canonicalValue($attribute, $value);
            $canonicalUnit = $value?->canonical_unit ?: $attribute->canonicalMeasurementUnit?->code;
            $sourceUnit = filled($canonicalUnit) ? $this->measurementUnit($canonicalUnit) : null;
            $display = $this->displayValue($attribute, $canonicalValue, $canonicalUnit, $site, $locale, $sourceUnit);
            $referencedCodes = match ($attribute->data_type) {
                AttributeDataType::Enum => [$canonicalValue],
                AttributeDataType::MultiEnum => is_array($canonicalValue) ? $canonicalValue : [],
                default => [],
            };
            $options = $attribute->options->filter(fn ($option) => $option->is_visible || in_array($option->code, $referencedCodes, true))->values()->map(fn ($option) => ['code' => $option->code,
                'label' => $this->translatedString($option, 'label', $locale, $option->label), 'is_visible' => $option->is_visible])->all();
            if (! in_array($attribute->data_type, [AttributeDataType::Integer, AttributeDataType::Decimal], true)) {
                $labels = array_column($options, 'label', 'code');
                $display['value'] = match ($attribute->data_type) {
                    AttributeDataType::Boolean => $canonicalValue === null ? null : ($canonicalValue ? 'Yes' : 'No'),
                    AttributeDataType::Enum => $canonicalValue === null ? null : ($labels[$canonicalValue] ?? $canonicalValue),
                    AttributeDataType::MultiEnum => is_array($canonicalValue) ? implode(', ', array_map(fn ($code) => $labels[$code] ?? $code, $canonicalValue)) : null,
                    AttributeDataType::Json => $canonicalValue === null ? null : json_encode($canonicalValue, JSON_THROW_ON_ERROR),
                    default => $canonicalValue,
                };
            }
            $section = $assignment->section;
            $payload[] = [
                'assignment_id' => $assignment->id, 'definition_id' => $attribute->id, 'code' => $attribute->code,
                'label' => $this->translatedString($attribute, 'label', $locale, $attribute->name), 'data_type' => $attribute->data_type->value,
                'measurement_dimension_id' => $attribute->measurement_dimension_id,
                'canonical_measurement_unit_id' => $attribute->canonical_measurement_unit_id,
                'has_value' => $value !== null, 'canonical_value' => $canonicalValue, 'canonical_unit' => $canonicalUnit,
                'canonical_unit_label' => $this->unitLabel($canonicalUnit, $locale, $sourceUnit),
                'display_value' => $display['value'], 'display_unit' => $display['unit'],
                'display_unit_label' => $this->unitLabel($display['unit'], $locale, $display['unit_model']),
                'options' => $options, 'position' => $assignment->position, 'is_required' => $assignment->is_required,
                'is_visible' => $assignment->is_visible, 'is_searchable' => $assignment->is_searchable, 'is_sortable' => $assignment->is_sortable,
                'section' => ['id' => $section?->id, 'code' => $section === null ? 'ungrouped' : $section->code,
                    'label' => $section === null ? 'Ungrouped' : $this->translatedString($section, 'name', $locale, $section->name),
                    'position' => $section === null ? PHP_INT_MAX : $section->position, 'is_visible' => $section === null ? true : $section->is_visible],
            ];
        }

        return $payload;
    }

    /** @param list<array<string, mixed>> $attributes
     * @return list<array<string, mixed>>
     */
    private function specSections(array $attributes): array
    {
        $sections = [];
        foreach ($attributes as $attribute) {
            if (! $attribute['is_visible'] || ! $attribute['section']['is_visible'] || ! $attribute['has_value']) {
                continue;
            }
            $key = $attribute['section']['id'] ?? 'ungrouped';
            $sections[$key] ??= [...$attribute['section'], 'attributes' => []];
            $sections[$key]['attributes'][] = $attribute;
        }

        return array_values($sections);
    }

    /** @param list<array<string, mixed>> $attributes
     * @return array<string, mixed>
     */
    private function facetValues(CentralProduct $product, array $attributes, ?float $rating): array
    {
        $facts = array_column($attributes, null, 'assignment_id');
        $values = [];
        foreach (FacetDefinition::query()->where('category_id', $product->central_category_id)->active()->where('is_filterable', true)->ordered()->get() as $facet) {
            $values[$facet->code] = match ($facet->source_type->value) {
                'attribute' => $facts[$facet->category_attribute_assignment_id]['canonical_value'] ?? null,
                'brand' => $product->brand?->slug,
                'rating' => $rating,
            };
        }

        return $values;
    }

    /** @return list<array<string, mixed>> */
    private function comparisonRows(CentralProduct $product): array
    {
        return CategoryComparisonAttribute::query()->where('central_category_id', $product->central_category_id)
            ->where('is_visible', true)->orderBy('position')->orderBy('id')->get(['id', 'category_attribute_assignment_id', 'position'])->toArray();
    }

    private function canonicalValue(
        AttributeDefinition $attribute,
        CentralProductAttributeValue $value,
    ): mixed {
        return match ($attribute->data_type) {
            AttributeDataType::Integer, AttributeDataType::Decimal => $value->getAttribute('canonical_value') ?? $value->getAttribute('value_number'),
            AttributeDataType::String, AttributeDataType::Text => $value->getAttribute('value_text'),
            AttributeDataType::Boolean => $value->getAttribute('value_bool'),
            AttributeDataType::Enum => $value->getAttribute('value_enum_code'),
            AttributeDataType::MultiEnum, AttributeDataType::Json => $value->getAttribute('value_json'),
        };
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

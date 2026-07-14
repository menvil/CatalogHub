<?php

namespace App\Domains\Projections\Builders;

use App\Domains\Projections\DTO\CategoryProjectionData;
use App\Domains\Seo\SeoProjectionBuilder;
use App\Enums\CentralCategoryStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\Site;
use App\Services\Sites\SiteOverrideResolver;
use App\Services\Translations\TranslationResolver;

final class CategoryProjectionBuilder
{
    public function __construct(
        private readonly TranslationResolver $translationResolver,
        private readonly SiteOverrideResolver $siteOverrideResolver,
        private readonly SeoProjectionBuilder $seoProjectionBuilder,
    ) {}

    public function build(Site $site, CentralCategory $category, string $locale): CategoryProjectionData
    {
        $category->loadMissing('parent');

        $sourceTitle = (string) $category->getAttribute('name');
        $title = $this->overrideString(
            $site,
            $category,
            ['title', 'local_title'],
            $locale,
            $this->translatedString($category, 'name', $locale, $sourceTitle),
        );
        $slug = $this->overrideString(
            $site,
            $category,
            ['slug', 'local_slug'],
            $locale,
            (string) $category->getAttribute('slug'),
        );
        $visibility = $this->siteOverrideResolver->resolve(
            $site,
            'category',
            (int) $category->getKey(),
            'visibility',
            $locale,
            fallbackValue: 'visible',
        );
        $status = $category->status === CentralCategoryStatus::Active && $this->isVisible($visibility)
            ? 'active'
            : 'pending';
        $definitions = AttributeDefinition::query()
            ->where('central_category_id', $category->getKey())
            ->visible()
            ->with(['options' => fn ($query) => $query->where('is_visible', true)->ordered()])
            ->ordered()
            ->get();
        $facets = $definitions
            ->filter(fn (AttributeDefinition $definition): bool => (bool) $definition->getAttribute('is_filterable'))
            ->map(fn (AttributeDefinition $definition): array => $this->attributeConfig($definition, $locale))
            ->values()
            ->all();
        $comparison = $definitions
            ->filter(fn (AttributeDefinition $definition): bool => (bool) $definition->getAttribute('is_comparable'))
            ->map(fn (AttributeDefinition $definition): array => $this->attributeConfig($definition, $locale))
            ->values()
            ->all();
        $children = CentralCategory::query()
            ->where('parent_id', $category->getKey())
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->map(fn (CentralCategory $child): array => $this->categoryReference($site, $child, $locale))
            ->values()
            ->all();
        $seo = $this->seoProjectionBuilder->forCategory(
            $site,
            $category,
            $locale,
            $title,
            $slug,
            $status === 'active',
        );
        $payload = [
            'category' => [
                'id' => (int) $category->getKey(),
                'title' => $title,
                'source_title' => $sourceTitle,
                'slug' => $slug,
                'status' => $category->status->value,
                'schema_status' => $category->schema_status->value,
                'visibility' => $visibility,
                'description' => $this->translatedString($category, 'description', $locale),
                'intro_text' => $this->overrideString($site, $category, ['intro_text'], $locale),
                'hero_text' => $this->overrideString($site, $category, ['hero_text'], $locale),
            ],
            'parent' => $category->parent instanceof CentralCategory
                ? $this->categoryReference($site, $category->parent, $locale)
                : null,
            'children' => $children,
            'facets' => $facets,
            'comparison' => $comparison,
            'seo' => $seo,
            'site' => [
                'id' => (int) $site->getKey(),
                'code' => (string) $site->getAttribute('code'),
                'locale' => $locale,
            ],
        ];
        $checksum = hash('sha256', json_encode(
            compact('status', 'payload', 'seo', 'facets', 'comparison'),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ));

        return new CategoryProjectionData(
            siteId: (int) $site->getKey(),
            locale: $locale,
            centralCategoryId: (int) $category->getKey(),
            parentCategoryId: $category->getAttribute('parent_id') === null
                ? null
                : (int) $category->getAttribute('parent_id'),
            slug: $slug,
            title: $title,
            status: $status,
            payload: $payload,
            seo: $seo,
            facets: $facets,
            comparison: $comparison,
            checksum: $checksum,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function categoryReference(Site $site, CentralCategory $category, string $locale): array
    {
        return [
            'id' => (int) $category->getKey(),
            'title' => $this->overrideString(
                $site,
                $category,
                ['title', 'local_title'],
                $locale,
                $this->translatedString(
                    $category,
                    'name',
                    $locale,
                    (string) $category->getAttribute('name'),
                ),
            ),
            'slug' => $this->overrideString(
                $site,
                $category,
                ['slug', 'local_slug'],
                $locale,
                (string) $category->getAttribute('slug'),
            ),
            'status' => $category->status->value,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function attributeConfig(AttributeDefinition $definition, string $locale): array
    {
        return [
            'attribute_id' => (int) $definition->getKey(),
            'code' => (string) $definition->getAttribute('code'),
            'label' => $this->translationResolver->resolve($definition, 'label', $locale)->value
                ?: $definition->getAttribute('name'),
            'data_type' => $definition->data_type->value,
            'canonical_unit' => $definition->getAttribute('canonical_unit'),
            'options' => $definition->options
                ->map(fn ($option): array => [
                    'code' => (string) $option->getAttribute('code'),
                    'label' => $this->translationResolver->resolve($option, 'label', $locale)->value
                        ?: $option->getAttribute('label'),
                ])
                ->values()
                ->all(),
        ];
    }

    private function translatedString(
        CentralCategory $category,
        string $field,
        string $locale,
        ?string $fallback = null,
    ): ?string {
        $value = $this->translationResolver->resolve($category, $field, $locale)->value;

        return is_scalar($value) ? (string) $value : $fallback;
    }

    /**
     * @param  list<string>  $fields
     */
    private function overrideString(
        Site $site,
        CentralCategory $category,
        array $fields,
        string $locale,
        ?string $fallback = null,
    ): ?string {
        $value = $fallback;

        foreach ($fields as $field) {
            $resolved = $this->siteOverrideResolver->resolve(
                $site,
                'category',
                (int) $category->getKey(),
                $field,
                $locale,
                fallbackValue: $value,
            );
            $value = is_scalar($resolved) ? (string) $resolved : $value;
        }

        return $value;
    }

    private function isVisible(mixed $visibility): bool
    {
        if (is_bool($visibility)) {
            return $visibility;
        }

        return ! in_array(
            mb_strtolower((string) $visibility),
            ['0', 'false', 'hidden', 'disabled', 'inactive'],
            true,
        );
    }
}

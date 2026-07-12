<?php

namespace App\Queries\Translations;

use App\Enums\TranslationStatus;
use App\Models\Translations\AttributeOptionTranslation;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\ProductTranslation;
use App\Models\Translations\UnitTranslation;

final class OutdatedTranslationsQuery
{
    /**
     * @return list<array{entity_type: string, source_label: string, translated_label: string, locale: string, status: string, updated_at: string, editor_url: string}>
     */
    public function get(?string $locale = null, ?string $entityType = null): array
    {
        $items = [];

        foreach ($this->configs() as $config) {
            if ($entityType !== null && $entityType !== $config['type']) {
                continue;
            }

            $translations = $config['model']::query()
                ->with($config['relation'])
                ->where('status', TranslationStatus::Outdated)
                ->when($locale, fn ($query) => $query->where('locale', $locale))
                ->limit(100)
                ->get();

            foreach ($translations as $translation) {
                $entity = $translation->getRelation($config['relation']);

                $items[] = [
                    'entity_type' => $config['type'],
                    'source_label' => (string) $entity?->getAttribute($config['source_label']),
                    'translated_label' => (string) $translation->getAttribute($config['translated_label']),
                    'locale' => (string) $translation->getAttribute('locale'),
                    'status' => $translation->getAttribute('status') instanceof TranslationStatus
                        ? $translation->getAttribute('status')->value
                        : (string) $translation->getAttribute('status'),
                    'updated_at' => (string) $translation->updated_at,
                    'editor_url' => $entity === null ? '#' : route($config['route'], [$entity, $translation->localeModel]),
                ];
            }
        }

        return $items;
    }

    /**
     * @return list<array{type: string, model: class-string, relation: string, source_label: string, translated_label: string, route: string}>
     */
    private function configs(): array
    {
        return [
            ['type' => 'product', 'model' => ProductTranslation::class, 'relation' => 'product', 'source_label' => 'name', 'translated_label' => 'name', 'route' => 'central.products.translations.edit'],
            ['type' => 'category', 'model' => CategoryTranslation::class, 'relation' => 'category', 'source_label' => 'name', 'translated_label' => 'name', 'route' => 'central.categories.translations.edit'],
            ['type' => 'attribute', 'model' => AttributeTranslation::class, 'relation' => 'attributeDefinition', 'source_label' => 'name', 'translated_label' => 'label', 'route' => 'central.attributes.translations.edit'],
            ['type' => 'section', 'model' => AttributeSectionTranslation::class, 'relation' => 'attributeSection', 'source_label' => 'name', 'translated_label' => 'name', 'route' => 'central.attribute-sections.translations.edit'],
            ['type' => 'option', 'model' => AttributeOptionTranslation::class, 'relation' => 'attributeOption', 'source_label' => 'label', 'translated_label' => 'label', 'route' => 'central.attribute-options.translations.edit'],
            ['type' => 'unit', 'model' => UnitTranslation::class, 'relation' => 'measurementUnit', 'source_label' => 'name', 'translated_label' => 'short_name', 'route' => 'central.units.translations.edit'],
        ];
    }
}

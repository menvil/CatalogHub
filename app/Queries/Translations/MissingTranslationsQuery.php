<?php

namespace App\Queries\Translations;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\MeasurementUnit;

final class MissingTranslationsQuery
{
    /**
     * @return list<array{entity_type: string, entity_id: int, source_label: string, locale: string, editor_url: string}>
     */
    public function get(?string $locale = null, ?string $entityType = null, ?string $search = null): array
    {
        $locales = Locale::query()
            ->active()
            ->when($locale, fn ($query) => $query->where('code', $locale))
            ->orderBy('position')
            ->orderBy('code')
            ->get();

        $items = [];

        foreach ($locales as $activeLocale) {
            foreach ($this->entityConfigs() as $config) {
                if ($entityType !== null && $entityType !== $config['type']) {
                    continue;
                }

                $query = $config['model']::query()
                    ->whereDoesntHave('translations', fn ($translationQuery) => $translationQuery->where('locale', $activeLocale->code));

                if ($search !== null && $search !== '') {
                    $escapedSearch = addcslashes($search, '\%_');
                    $column = $query->getQuery()->getGrammar()->wrap($config['label']);

                    $query->whereRaw("{$column} like ? escape '\\'", ["%{$escapedSearch}%"]);
                }

                foreach ($query->limit(100)->get() as $entity) {
                    $items[] = [
                        'entity_type' => $config['type'],
                        'entity_id' => (int) $entity->getKey(),
                        'source_label' => (string) $entity->getAttribute($config['label']),
                        'locale' => $activeLocale->code,
                        'editor_url' => route($config['route'], [$entity, $activeLocale]),
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * @return list<array{type: string, model: class-string, label: string, route: string}>
     */
    private function entityConfigs(): array
    {
        return [
            ['type' => 'product', 'model' => CentralProduct::class, 'label' => 'name', 'route' => 'central.products.translations.edit'],
            ['type' => 'category', 'model' => CentralCategory::class, 'label' => 'name', 'route' => 'central.categories.translations.edit'],
            ['type' => 'attribute', 'model' => AttributeDefinition::class, 'label' => 'name', 'route' => 'central.attributes.translations.edit'],
            ['type' => 'section', 'model' => AttributeSection::class, 'label' => 'name', 'route' => 'central.attribute-sections.translations.edit'],
            ['type' => 'option', 'model' => AttributeOption::class, 'label' => 'label', 'route' => 'central.attribute-options.translations.edit'],
            ['type' => 'unit', 'model' => MeasurementUnit::class, 'label' => 'name', 'route' => 'central.units.translations.edit'],
        ];
    }
}

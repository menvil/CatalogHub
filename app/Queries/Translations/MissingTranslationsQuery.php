<?php

namespace App\Queries\Translations;

use App\Contracts\Persistence\RawSqlPersistenceBoundary;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Support\Database\LiteralLikePattern;

final class MissingTranslationsQuery implements RawSqlPersistenceBoundary
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

        $localeCodes = $locales->pluck('code')->all();

        foreach ($this->entityConfigs() as $config) {
            if ($entityType !== null && $entityType !== $config['type']) {
                continue;
            }

            $query = $config['model']::query()
                ->with(['translations' => fn ($translationQuery) => $translationQuery->whereIn('locale', $localeCodes)])
                ->orderBy($config['model']::query()->getModel()->getKeyName());

            if ($search !== null && $search !== '') {
                $column = $query->getQuery()->getGrammar()->wrap($config['label']);

                $query->whereRaw("{$column} LIKE ? ESCAPE '!'", [LiteralLikePattern::containing($search)]);
            }

            foreach ($query->lazy() as $entity) {
                $existingLocales = $entity->translations->pluck('locale');

                foreach ($locales as $activeLocale) {
                    if ($existingLocales->contains($activeLocale->code)) {
                        continue;
                    }

                    $items[] = [
                        'entity_type' => $config['type'],
                        'entity_id' => (int) $entity->getKey(),
                        'source_label' => (string) $entity->getAttribute($config['label']),
                        'locale' => $activeLocale->code,
                        'editor_url' => route($config['route'], [
                            $entity,
                            $config['locale_by_code'] ? $activeLocale->code : $activeLocale,
                        ]),
                    ];
                }
            }
        }

        return $items;
    }

    /**
     * @return list<array{type: string, model: class-string, label: string, route: string, locale_by_code: bool}>
     */
    private function entityConfigs(): array
    {
        return [
            ['type' => 'brand', 'model' => CentralBrand::class, 'label' => 'name', 'route' => 'central.brands.translations.edit', 'locale_by_code' => true],
            ['type' => 'product', 'model' => CentralProduct::class, 'label' => 'name', 'route' => 'central.products.translations.edit', 'locale_by_code' => false],
            ['type' => 'category', 'model' => CentralCategory::class, 'label' => 'name', 'route' => 'central.categories.translations.edit', 'locale_by_code' => false],
            ['type' => 'attribute', 'model' => AttributeDefinition::class, 'label' => 'name', 'route' => 'central.attributes.translations.edit', 'locale_by_code' => false],
            ['type' => 'section', 'model' => AttributeSection::class, 'label' => 'name', 'route' => 'central.attribute-sections.translations.edit', 'locale_by_code' => false],
            ['type' => 'option', 'model' => AttributeOption::class, 'label' => 'label', 'route' => 'central.attribute-options.translations.edit', 'locale_by_code' => false],
            ['type' => 'unit', 'model' => MeasurementUnit::class, 'label' => 'name', 'route' => 'central.units.translations.edit', 'locale_by_code' => false],
        ];
    }
}

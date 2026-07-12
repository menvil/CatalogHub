<?php

namespace App\Services\Translations;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Models\Translations\AttributeOptionTranslation;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\ProductTranslation;
use App\Models\Translations\UnitTranslation;
use App\ValueObjects\Translations\ResolvedTranslation;
use Illuminate\Database\Eloquent\Model;

final class TranslationResolver
{
    public function resolve(Model $entity, string $field, string $locale): ResolvedTranslation
    {
        $config = $this->configFor($entity);

        foreach ($this->candidateLocales($locale) as $candidate) {
            $translation = $config['translation']::query()
                ->where($config['foreign_key'], $entity->getKey())
                ->where('locale', $candidate)
                ->first();

            if ($translation instanceof Model && filled($translation->getAttribute($field))) {
                return new ResolvedTranslation(
                    value: $translation->getAttribute($field),
                    locale: (string) $translation->getAttribute('locale'),
                    status: $this->statusOf($translation),
                    source: $candidate === $locale ? 'exact' : 'fallback_locale',
                    translationModel: $translation,
                );
            }
        }

        if ($entity->getAttribute($field) !== null) {
            return new ResolvedTranslation(
                value: $entity->getAttribute($field),
                locale: $locale,
                status: TranslationStatus::Missing,
                source: 'source',
            );
        }

        return new ResolvedTranslation(
            value: null,
            locale: $locale,
            status: TranslationStatus::Missing,
            source: 'missing',
        );
    }

    /**
     * @return list<string>
     */
    private function candidateLocales(string $locale): array
    {
        $candidates = [$locale];
        $language = str($locale)->before('-')->toString();

        if ($language !== $locale && $language !== '') {
            $sameLanguageLocales = Locale::query()
                ->active()
                ->where('language_code', $language)
                ->where('code', '!=', $locale)
                ->orderByDesc('is_default')
                ->orderBy('position')
                ->pluck('code')
                ->all();

            $candidates = [...$candidates, ...$sameLanguageLocales];
        }

        $defaultLocale = Locale::query()
            ->active()
            ->where('is_default', true)
            ->value('code');

        if (is_string($defaultLocale) && $defaultLocale !== '') {
            $candidates[] = $defaultLocale;
        }

        return array_values(array_unique($candidates));
    }

    /**
     * @return array{translation: class-string<Model>, foreign_key: string}
     */
    private function configFor(Model $entity): array
    {
        return match (true) {
            $entity instanceof CentralProduct => [
                'translation' => ProductTranslation::class,
                'foreign_key' => 'product_id',
            ],
            $entity instanceof CentralCategory => [
                'translation' => CategoryTranslation::class,
                'foreign_key' => 'category_id',
            ],
            $entity instanceof AttributeDefinition => [
                'translation' => AttributeTranslation::class,
                'foreign_key' => 'attribute_definition_id',
            ],
            $entity instanceof AttributeSection => [
                'translation' => AttributeSectionTranslation::class,
                'foreign_key' => 'attribute_section_id',
            ],
            $entity instanceof AttributeOption => [
                'translation' => AttributeOptionTranslation::class,
                'foreign_key' => 'attribute_option_id',
            ],
            $entity instanceof MeasurementUnit => [
                'translation' => UnitTranslation::class,
                'foreign_key' => 'measurement_unit_id',
            ],
            default => throw new \InvalidArgumentException('Unsupported translatable entity: '.$entity::class),
        };
    }

    private function statusOf(Model $translation): TranslationStatus
    {
        $status = $translation->getAttribute('status');

        return $status instanceof TranslationStatus
            ? $status
            : TranslationStatus::from((string) $status);
    }
}

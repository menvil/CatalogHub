<?php

namespace App\Observers;

use App\Domains\Projections\ProjectionStaleDetector;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\MeasurementUnit;
use App\Models\Translations\AttributeOptionTranslation;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\UnitTranslation;
use Illuminate\Database\Eloquent\Model;

final class SchemaTranslationProjectionObserver
{
    public function saved(Model $translation): void
    {
        if (! $translation->wasRecentlyCreated && ! $translation->wasChanged(['label', 'short_label', 'help_text', 'description', 'name', 'title', 'symbol', 'short_name', 'long_name', 'plural_name', 'symbol_position', 'space_between_value_and_unit', 'status'])) {
            return;
        }
        $this->invalidate($translation);
    }

    public function deleted(Model $translation): void
    {
        $this->invalidate($translation);
    }

    private function invalidate(Model $translation): void
    {
        $definitionIds = match (true) {
            $translation instanceof AttributeTranslation => [$translation->attribute_definition_id],
            $translation instanceof AttributeOptionTranslation => AttributeOption::query()->whereKey($translation->attribute_option_id)->pluck('attribute_definition_id')->all(),
            $translation instanceof UnitTranslation => AttributeDefinition::query()->where(function ($query) use ($translation): void {
                $dimensionId = MeasurementUnit::query()->whereKey($translation->measurement_unit_id)->value('dimension_id');
                $query->where('canonical_measurement_unit_id', $translation->measurement_unit_id);
                if ($dimensionId !== null) {
                    $query->orWhere('measurement_dimension_id', $dimensionId);
                }
            })->pluck('id')->all(),
            default => [],
        };
        $categoryIds = match (true) {
            $translation instanceof CategoryTranslation => [$translation->category_id],
            $translation instanceof AttributeSectionTranslation => AttributeSection::query()->whereKey($translation->attribute_section_id)->pluck('central_category_id')->all(),
            default => CategoryAttributeAssignment::query()->whereIn('attribute_definition_id', $definitionIds)->distinct()->pluck('central_category_id')->all(),
        };
        foreach (CentralCategory::query()->whereIn('id', $categoryIds)->orderBy('id')->cursor() as $category) {
            app(ProjectionStaleDetector::class)->markStaleForCategory($category, 'schema_translation_updated');
        }
    }
}

<?php

namespace App\Queries\Translations;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\MeasurementUnit;
use App\Models\Translations\AttributeOptionTranslation;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\ProductTranslation;
use App\Models\Translations\UnitTranslation;

final class TranslationEditorQuery
{
    public function product(CentralProduct $product, string $locale): ?ProductTranslation
    {
        return $product->translations()->where('locale', $locale)->first();
    }

    public function category(CentralCategory $category, string $locale): ?CategoryTranslation
    {
        return $category->translations()->where('locale', $locale)->first();
    }

    public function attribute(AttributeDefinition $attribute, string $locale): ?AttributeTranslation
    {
        return $attribute->translations()->where('locale', $locale)->first();
    }

    public function section(AttributeSection $section, string $locale): ?AttributeSectionTranslation
    {
        return $section->translations()->where('locale', $locale)->first();
    }

    public function option(AttributeOption $option, string $locale): ?AttributeOptionTranslation
    {
        return $option->translations()->where('locale', $locale)->first();
    }

    public function unit(MeasurementUnit $unit, string $locale): ?UnitTranslation
    {
        return $unit->translations()->where('locale', $locale)->first();
    }
}

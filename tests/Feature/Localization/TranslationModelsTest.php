<?php

namespace Tests\Feature\Localization;

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
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TranslationModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_category_translation(): void
    {
        $category = CentralCategory::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $translation = CategoryTranslation::factory()->create([
            'category_id' => $category->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'name' => 'Monitore',
            'status' => TranslationStatus::HumanReviewed,
        ]);

        $this->assertTrue($translation->category->is($category));
        $this->assertTrue($translation->localeModel->is($locale));
        $this->assertTrue($category->translations->contains($translation));
    }

    public function test_can_create_product_translation(): void
    {
        $product = CentralProduct::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $translation = ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'name' => 'LG UltraGear 27GP850-B Monitor',
        ]);

        $this->assertTrue($translation->product->is($product));
        $this->assertTrue($translation->localeModel->is($locale));
    }

    public function test_can_create_attribute_translation(): void
    {
        $attribute = AttributeDefinition::factory()->create(['code' => 'refresh_rate']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $translation = AttributeTranslation::factory()->create([
            'attribute_definition_id' => $attribute->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'label' => 'Bildwiederholfrequenz',
        ]);

        $this->assertTrue($translation->attributeDefinition->is($attribute));
    }

    public function test_can_create_attribute_section_translation(): void
    {
        $section = AttributeSection::factory()->create(['code' => 'display']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $translation = AttributeSectionTranslation::factory()->create([
            'attribute_section_id' => $section->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'name' => 'Bildschirm',
        ]);

        $this->assertTrue($translation->attributeSection->is($section));
    }

    public function test_can_create_attribute_option_translation(): void
    {
        $option = AttributeOption::factory()->create(['code' => 'ips']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        $translation = AttributeOptionTranslation::factory()->create([
            'attribute_option_id' => $option->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'label' => 'IPS',
        ]);

        $this->assertTrue($translation->attributeOption->is($option));
    }

    public function test_can_create_unit_translation(): void
    {
        $unit = MeasurementUnit::factory()->create(['code' => 'watt']);
        $locale = Locale::factory()->create(['code' => 'ru-RU']);

        $translation = UnitTranslation::factory()->create([
            'measurement_unit_id' => $unit->id,
            'locale_id' => $locale->id,
            'locale' => 'ru-RU',
            'short_name' => 'Вт',
            'long_name' => 'ватт',
            'plural_name' => 'ватт',
        ]);

        $this->assertTrue($translation->measurementUnit->is($unit));
    }

    public function test_product_translation_locale_is_unique_per_product(): void
    {
        $this->expectException(QueryException::class);

        $product = CentralProduct::factory()->create();
        $locale = Locale::factory()->create(['code' => 'de-DE']);

        ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
        ]);
        ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
        ]);
    }

    public function test_translation_factories_keep_locale_string_consistent_with_locale_model(): void
    {
        $translations = [
            ProductTranslation::factory()->create(),
            CategoryTranslation::factory()->create(),
            AttributeTranslation::factory()->create(),
            AttributeSectionTranslation::factory()->create(),
            AttributeOptionTranslation::factory()->create(),
            UnitTranslation::factory()->create(),
        ];

        foreach ($translations as $translation) {
            $this->assertSame($translation->localeModel->code, $translation->locale);
        }
    }
}

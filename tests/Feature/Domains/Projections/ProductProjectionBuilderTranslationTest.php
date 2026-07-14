<?php

namespace Tests\Feature\Domains\Projections;

use App\Domains\Projections\Builders\ProductProjectionBuilder;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeOption;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\Locale;
use App\Models\MeasurementUnit;
use App\Models\Site;
use App\Models\Translations\AttributeOptionTranslation;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\ProductTranslation;
use App\Models\Translations\UnitTranslation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductProjectionBuilderTranslationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uses_translated_labels_and_falls_back_to_source_values(): void
    {
        $locale = Locale::factory()->create(['code' => 'de-DE', 'is_default' => true]);
        $site = Site::factory()->create(['default_locale' => 'de-DE']);
        $category = CentralCategory::factory()->create(['name' => 'Monitors']);
        $section = AttributeSection::factory()->for($category, 'category')->create([
            'code' => 'display',
            'name' => 'Display',
        ]);
        $panelType = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => 'panel_type',
                'name' => 'Panel type',
                'data_type' => 'enum',
                'position' => 1,
            ]);
        $gaming = AttributeOption::factory()->for($panelType, 'attribute')->create([
            'code' => 'gaming',
            'label' => 'Gaming',
        ]);
        $power = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => 'power',
                'name' => 'Power',
                'data_type' => 'integer',
                'canonical_unit' => 'watt',
                'position' => 2,
            ]);
        $brightness = AttributeDefinition::factory()
            ->for($category, 'category')
            ->for($section, 'section')
            ->create([
                'code' => 'brightness',
                'name' => 'Brightness',
                'data_type' => 'integer',
                'position' => 3,
            ]);
        $product = CentralProduct::factory()->for($category, 'category')->create([
            'name' => 'Central Product Name',
        ]);
        $unit = MeasurementUnit::factory()->create([
            'code' => 'watt',
            'name' => 'Watt',
            'symbol' => 'W',
        ]);

        ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'name' => 'Lokaler Produktname',
            'short_description' => 'Lokale Kurzbeschreibung',
            'status' => TranslationStatus::Approved,
        ]);
        CategoryTranslation::factory()->create([
            'category_id' => $category->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'name' => 'Monitore',
        ]);
        AttributeSectionTranslation::factory()->create([
            'attribute_section_id' => $section->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'name' => 'Bildschirm',
        ]);
        AttributeTranslation::factory()->create([
            'attribute_definition_id' => $panelType->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'label' => 'Paneltyp',
        ]);
        AttributeOptionTranslation::factory()->create([
            'attribute_option_id' => $gaming->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'label' => 'Spiele',
        ]);
        UnitTranslation::factory()->create([
            'measurement_unit_id' => $unit->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'short_name' => 'W-de',
        ]);

        CentralProductAttributeValue::factory()->for($product, 'product')->for($panelType, 'attributeDefinition')->create([
            'value_type' => 'enum',
            'value_enum_code' => 'gaming',
        ]);
        CentralProductAttributeValue::factory()->for($product, 'product')->for($power, 'attributeDefinition')->create([
            'value_type' => 'integer',
            'value_number' => 100,
            'canonical_value' => 100,
            'canonical_unit' => 'watt',
        ]);
        CentralProductAttributeValue::factory()->for($product, 'product')->for($brightness, 'attributeDefinition')->create([
            'value_type' => 'integer',
            'value_number' => 400,
            'canonical_value' => 400,
        ]);

        $projection = app(ProductProjectionBuilder::class)->build($site, $product, 'de-DE');
        $attributes = $projection->payload['spec_sections'][0]['attributes'];

        $this->assertSame('Lokaler Produktname', $projection->title);
        $this->assertSame('Lokaler Produktname', $projection->payload['product']['title']);
        $this->assertSame('Lokale Kurzbeschreibung', $projection->payload['product']['short_description']);
        $this->assertSame('Monitore', $projection->payload['category']['label']);
        $this->assertSame('Bildschirm', $projection->payload['spec_sections'][0]['label']);
        $this->assertSame('Paneltyp', $attributes[0]['label']);
        $this->assertSame([['code' => 'gaming', 'label' => 'Spiele']], $attributes[0]['options']);
        $this->assertSame('W-de', $attributes[1]['canonical_unit_label']);
        $this->assertSame('Brightness', $attributes[2]['label']);
    }
}

<?php

namespace Tests\Feature\Actions;

use App\Actions\Translations\DetectOutdatedTranslationsAction;
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
use App\Services\Translations\TranslationSourceHashService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DetectOutdatedTranslationsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_marks_product_translation_outdated_when_source_hash_changed(): void
    {
        $product = CentralProduct::factory()->create(['name' => 'Old Name']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $hash = app(TranslationSourceHashService::class)->forProduct($product);

        $translation = ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'name' => 'Alter Name',
            'source_hash' => $hash,
            'status' => TranslationStatus::Approved,
        ]);

        $product->update(['name' => 'New Name']);

        app(DetectOutdatedTranslationsAction::class)->handle($product);

        $this->assertSame(TranslationStatus::Outdated, $translation->fresh()->status);
    }

    public function test_does_not_mark_translation_outdated_when_source_hash_is_unchanged(): void
    {
        $product = CentralProduct::factory()->create(['name' => 'Name']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $hash = app(TranslationSourceHashService::class)->forProduct($product);

        $translation = ProductTranslation::factory()->create([
            'product_id' => $product->id,
            'locale_id' => $locale->id,
            'locale' => 'de-DE',
            'source_hash' => $hash,
            'status' => TranslationStatus::Approved,
        ]);

        app(DetectOutdatedTranslationsAction::class)->handle($product);

        $this->assertSame(TranslationStatus::Approved, $translation->fresh()->status);
    }

    public function test_detects_outdated_translations_for_all_translatable_entities(): void
    {
        $this->assertCategoryOutdatedDetection(true);
        $this->assertAttributeOutdatedDetection(true);
        $this->assertAttributeSectionOutdatedDetection(true);
        $this->assertAttributeOptionOutdatedDetection(true);
        $this->assertUnitOutdatedDetection(true);
    }

    public function test_leaves_unchanged_source_hashes_for_all_translatable_entities(): void
    {
        $this->assertCategoryOutdatedDetection(false);
        $this->assertAttributeOutdatedDetection(false);
        $this->assertAttributeSectionOutdatedDetection(false);
        $this->assertAttributeOptionOutdatedDetection(false);
        $this->assertUnitOutdatedDetection(false);
    }

    private function assertCategoryOutdatedDetection(bool $mutate): void
    {
        $hashService = app(TranslationSourceHashService::class);
        $category = CentralCategory::factory()->create(['name' => 'Old category']);
        $locale = Locale::factory()->create();
        $hash = $hashService->forCategory($category);
        $translation = CategoryTranslation::factory()->create([
            'category_id' => $category->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'source_hash' => $hash,
            'status' => TranslationStatus::Approved,
        ]);

        if ($mutate) {
            $category->update(['name' => 'New category']);
        }

        $updated = app(DetectOutdatedTranslationsAction::class)->handle($category);

        $this->assertSame($mutate ? 1 : 0, $updated, 'category');
        $this->assertSame($mutate ? TranslationStatus::Outdated : TranslationStatus::Approved, $translation->fresh()->status, 'category');
    }

    private function assertAttributeOutdatedDetection(bool $mutate): void
    {
        $hashService = app(TranslationSourceHashService::class);
        $attribute = AttributeDefinition::factory()->create(['name' => 'Old attribute']);
        $locale = Locale::factory()->create();
        $hash = $hashService->forAttribute($attribute);
        $translation = AttributeTranslation::factory()->create([
            'attribute_definition_id' => $attribute->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'source_hash' => $hash,
            'status' => TranslationStatus::Approved,
        ]);

        if ($mutate) {
            $attribute->update(['name' => 'New attribute']);
        }

        $updated = app(DetectOutdatedTranslationsAction::class)->handle($attribute);

        $this->assertSame($mutate ? 1 : 0, $updated, 'attribute');
        $this->assertSame($mutate ? TranslationStatus::Outdated : TranslationStatus::Approved, $translation->fresh()->status, 'attribute');
    }

    private function assertAttributeSectionOutdatedDetection(bool $mutate): void
    {
        $hashService = app(TranslationSourceHashService::class);
        $section = AttributeSection::factory()->create(['name' => 'Old section']);
        $locale = Locale::factory()->create();
        $hash = $hashService->forAttributeSection($section);
        $translation = AttributeSectionTranslation::factory()->create([
            'attribute_section_id' => $section->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'source_hash' => $hash,
            'status' => TranslationStatus::Approved,
        ]);

        if ($mutate) {
            $section->update(['name' => 'New section']);
        }

        $updated = app(DetectOutdatedTranslationsAction::class)->handle($section);

        $this->assertSame($mutate ? 1 : 0, $updated, 'section');
        $this->assertSame($mutate ? TranslationStatus::Outdated : TranslationStatus::Approved, $translation->fresh()->status, 'section');
    }

    private function assertAttributeOptionOutdatedDetection(bool $mutate): void
    {
        $hashService = app(TranslationSourceHashService::class);
        $option = AttributeOption::factory()->create(['label' => 'Old option']);
        $locale = Locale::factory()->create();
        $hash = $hashService->forAttributeOption($option);
        $translation = AttributeOptionTranslation::factory()->create([
            'attribute_option_id' => $option->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'source_hash' => $hash,
            'status' => TranslationStatus::Approved,
        ]);

        if ($mutate) {
            $option->update(['label' => 'New option']);
        }

        $updated = app(DetectOutdatedTranslationsAction::class)->handle($option);

        $this->assertSame($mutate ? 1 : 0, $updated, 'option');
        $this->assertSame($mutate ? TranslationStatus::Outdated : TranslationStatus::Approved, $translation->fresh()->status, 'option');
    }

    private function assertUnitOutdatedDetection(bool $mutate): void
    {
        $hashService = app(TranslationSourceHashService::class);
        $unit = MeasurementUnit::factory()->create(['name' => 'Old unit']);
        $locale = Locale::factory()->create();
        $hash = $hashService->forUnit($unit);
        $translation = UnitTranslation::factory()->create([
            'measurement_unit_id' => $unit->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'source_hash' => $hash,
            'status' => TranslationStatus::Approved,
        ]);

        if ($mutate) {
            $unit->update(['name' => 'New unit']);
        }

        $updated = app(DetectOutdatedTranslationsAction::class)->handle($unit);

        $this->assertSame($mutate ? 1 : 0, $updated, 'unit');
        $this->assertSame($mutate ? TranslationStatus::Outdated : TranslationStatus::Approved, $translation->fresh()->status, 'unit');
    }
}

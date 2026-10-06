<?php

namespace Tests\Feature\SchemaConsumers;

use App\Actions\Translations\ApproveTranslationAction;
use App\Actions\Translations\MarkTranslationOutdatedAction;
use App\Actions\Translations\SaveProductTranslationAction;
use App\Domains\Projections\Builders\ProductProjectionBuilder;
use App\Domains\Projections\SiteSyncService;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\CentralCatalog\CentralProductAttributeValue;
use App\Models\Locale;
use App\Models\MeasurementDimension;
use App\Models\MeasurementUnit;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProduct;
use App\Models\SiteProductProjection;
use App\Models\SiteSearchDocument;
use App\Models\Translations\AttributeOptionTranslation;
use App\Models\Translations\AttributeSectionTranslation;
use App\Models\Translations\AttributeTranslation;
use App\Models\Translations\CategoryTranslation;
use App\Models\Translations\UnitTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class SchemaConsumerTranslationFreshnessTest extends TestCase
{
    use RefreshDatabase;

    private function fixture(): array
    {
        $locale = Locale::factory()->create(['code' => 'en-US']);
        $site = Site::factory()->create(['default_locale' => $locale->code]);
        $category = CentralCategory::factory()->create(['status' => 'active']);
        SiteCategory::query()->create(['site_id' => $site->id, 'central_category_id' => $category->id, 'is_enabled' => true]);
        $products = CentralProduct::factory()->count(2)->create(['central_category_id' => $category->id, 'status' => 'active']);
        foreach ($products as $product) {
            SiteProduct::factory()->create(['site_id' => $site->id, 'central_product_id' => $product->id]);
        }

        return [$locale, $site, $category, $products[0], $products[1]];
    }

    public function test_product_translation_save_approval_outdated_and_no_op_use_product_only_freshness(): void
    {
        [$locale, $site, $category, $product, $other] = $this->fixture();
        $actor = User::factory()->centralAdmin()->create();
        $save = app(SaveProductTranslationAction::class);
        $translation = $save->handle($product, $locale, ['name' => 'Old translated title']);
        app(SiteSyncService::class)->syncSite($site);
        $projection = SiteProductProjection::query()->where('central_product_id', $product->id)->sole();
        $search = SiteSearchDocument::query()->where('document_type', 'product')->where('document_id', $product->id)->sole();
        $version = $product->fresh()->version;
        $revision = $category->fresh()->schema_revision;
        $copy = ['name' => 'New translated title', 'subtitle' => 'New subtitle', 'short_description' => 'New short copy', 'description' => 'New description'];
        $translation = $save->handle($product, $locale, $copy);
        self::assertSame('stale', $projection->fresh()->getRawOriginal('status'));
        self::assertSame('stale', $search->fresh()->getRawOriginal('status'));
        self::assertSame('active', SiteProductProjection::query()->where('central_product_id', $other->id)->sole()->getRawOriginal('status'));
        self::assertSame('active', SiteCategoryProjection::query()->sole()->getRawOriginal('status'));
        self::assertSame($version, $product->fresh()->version);
        self::assertSame($revision, $category->fresh()->schema_revision);
        $rebuilt = app(SiteSyncService::class)->syncProduct($site, $product, $locale->code);
        self::assertSame('New translated title', $rebuilt->title);
        foreach (['subtitle', 'short_description', 'description'] as $field) {
            self::assertSame($copy[$field], $rebuilt->payload_json['product'][$field]);
        }
        self::assertSame('New translated title', $search->fresh()->title);
        self::assertSame(2, $rebuilt->attribute_identity_version);
        self::assertSame(2, $search->fresh()->attribute_identity_version);
        $save->handle($product, $locale, $copy);
        self::assertSame('active', $rebuilt->fresh()->getRawOriginal('status'));
        self::assertSame('active', $search->fresh()->getRawOriginal('status'));
        app(ApproveTranslationAction::class)->handle($translation, $actor);
        self::assertSame('stale', $rebuilt->fresh()->getRawOriginal('status'));
        self::assertSame('stale', $search->fresh()->getRawOriginal('status'));
        app(SiteSyncService::class)->syncProduct($site, $product, $locale->code);
        app(ApproveTranslationAction::class)->handle($translation->fresh(), $actor);
        self::assertSame('active', $rebuilt->fresh()->getRawOriginal('status'));
        app(MarkTranslationOutdatedAction::class)->handle($translation->fresh());
        self::assertSame('stale', $rebuilt->fresh()->getRawOriginal('status'));
        self::assertSame('stale', $search->fresh()->getRawOriginal('status'));
        app(SiteSyncService::class)->syncProduct($site, $product, $locale->code);
        app(MarkTranslationOutdatedAction::class)->handle($translation->fresh());
        self::assertSame('active', $rebuilt->fresh()->getRawOriginal('status'));
    }

    #[DataProvider('schemaTranslationOwners')]
    public function test_schema_translation_fan_out_keeps_category_and_product_search_invalidation(string $owner): void
    {
        [$locale, $site, $category] = $this->fixture();
        $otherCategory = CentralCategory::factory()->create(['status' => 'active']);
        SiteCategory::query()->create(['site_id' => $site->id, 'central_category_id' => $otherCategory->id, 'is_enabled' => true]);
        $otherProduct = CentralProduct::factory()->create(['central_category_id' => $otherCategory->id, 'status' => 'active']);
        SiteProduct::factory()->create(['site_id' => $site->id, 'central_product_id' => $otherProduct->id]);
        $dimension = MeasurementDimension::factory()->create();
        $unit = MeasurementUnit::factory()->create(['dimension_id' => $dimension->id]);
        $definition = AttributeDefinition::factory()->create($owner === 'unit' ? ['data_type' => 'decimal', 'measurement_dimension_id' => $dimension->id, 'canonical_measurement_unit_id' => $unit->id] : ['data_type' => 'enum']);
        $section = AttributeSection::factory()->create(['central_category_id' => $category->id]);
        CategoryAttributeAssignment::factory()->create(['central_category_id' => $category->id, 'attribute_definition_id' => $definition->id, 'attribute_section_id' => $section->id]);
        if (in_array($owner, ['attribute', 'option', 'unit'], true)) {
            CategoryAttributeAssignment::factory()->create(['central_category_id' => $otherCategory->id, 'attribute_definition_id' => $definition->id]);
        }
        $identity = ['locale_id' => $locale->id, 'locale' => $locale->code];
        [$translation, $field] = match ($owner) {
            'category' => [CategoryTranslation::factory()->create([...$identity, 'category_id' => $category->id]), 'name'],
            'attribute' => [AttributeTranslation::factory()->create([...$identity, 'attribute_definition_id' => $definition->id]), 'label'],
            'option' => [AttributeOptionTranslation::factory()->create([...$identity, 'attribute_option_id' => $definition->options()->create(['code' => 'shared', 'label' => 'Shared'])->id]), 'label'],
            'section' => [AttributeSectionTranslation::factory()->create([...$identity, 'attribute_section_id' => $section->id]), 'name'],
            'unit' => [UnitTranslation::factory()->create([...$identity, 'measurement_unit_id' => $unit->id]), 'long_name'],
            default => throw new \InvalidArgumentException('Unknown translation owner.'),
        };
        app(SiteSyncService::class)->syncSite($site);
        $translation->update([$field => 'Changed translation']);
        self::assertSame(0, SiteProductProjection::query()->whereIn('central_product_id', $category->products()->pluck('id'))->where('status', 'active')->count());
        self::assertSame('stale', SiteCategoryProjection::query()->where('central_category_id', $category->id)->sole()->getRawOriginal('status'));
        self::assertSame(0, SiteSearchDocument::query()->where('document_type', 'product')->whereIn('document_id', $category->products()->pluck('id'))->where('status', 'active')->count());
        $otherStatus = in_array($owner, ['attribute', 'option', 'unit'], true) ? 'stale' : 'active';
        self::assertSame($otherStatus, SiteProductProjection::query()->where('central_product_id', $otherProduct->id)->sole()->getRawOriginal('status'));
        self::assertSame($otherStatus, SiteCategoryProjection::query()->where('central_category_id', $otherCategory->id)->sole()->getRawOriginal('status'));
    }

    public static function schemaTranslationOwners(): array
    {
        return [['category'], ['attribute'], ['option'], ['section'], ['unit']];
    }

    #[DataProvider('optionTypes')]
    public function test_projection_options_include_visible_and_only_referenced_hidden_codes(string $type): void
    {
        [$locale, $site, $category, $product] = $this->fixture();
        $definition = AttributeDefinition::factory()->create(['data_type' => $type]);
        $assignment = CategoryAttributeAssignment::factory()->create(['central_category_id' => $category->id, 'attribute_definition_id' => $definition->id]);
        foreach (['visible' => true, 'stored_hidden' => false, 'unused_hidden' => false] as $code => $visible) {
            $definition->options()->create(['code' => $code, 'label' => $code === 'stored_hidden' ? 'Historical label' : $code, 'is_visible' => $visible]);
        }
        CentralProductAttributeValue::factory()->forAssignment($assignment)->create(['central_product_id' => $product->id, 'value_type' => $type, 'value_text' => null,
            'value_enum_code' => $type === 'enum' ? 'stored_hidden' : null, 'value_json' => $type === 'multi_enum' ? ['stored_hidden'] : null]);
        $projection = app(ProductProjectionBuilder::class)->build($site, $product, $locale->code);
        $attribute = $projection->payload['attributes'][0];
        self::assertSame(['visible', 'stored_hidden'], array_column($attribute['options'], 'code'));
        self::assertSame('Historical label', $attribute['display_value']);
    }

    public static function optionTypes(): array
    {
        return [['enum'], ['multi_enum']];
    }
}

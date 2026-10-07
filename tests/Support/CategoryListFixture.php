<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\AttributeSection;
use App\Models\CentralCatalog\CategoryAttributeAssignment;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\FacetDefinition;
use App\Models\Locale;
use App\Models\Market;
use App\Models\Site;
use App\Models\SiteCategory;
use App\Models\Translations\CategoryTranslation;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

final class CategoryListFixture
{
    public const VERSION = 'categories-list-v1';

    public static function create(): void
    {
        $at = CarbonImmutable::parse('2026-10-07T09:00:00Z');
        foreach (['en-US' => ['en', 'US', 'English'], 'de-DE' => ['de', 'DE', 'German']] as $code => [$language, $region, $name]) {
            Locale::query()->firstOrCreate(['code' => $code], ['language_code' => $language, 'region_code' => $region, 'name' => $name, 'native_name' => $name, 'direction' => 'ltr', 'is_active' => true, 'position' => 0]);
        }
        $locales = Locale::query()->active()->orderBy('position')->orderBy('code')->get();
        $market = Market::query()->firstOrCreate(['code' => 'ca016-fixture'], ['name' => 'CA-016 fixture', 'currency_code' => 'EUR', 'default_locale' => 'en-US', 'country_code' => 'DE', 'timezone' => 'UTC', 'status' => 'active']);
        foreach (['draft', 'active', 'archived', 'suspended'] as $i => $status) {
            $site = new Site;
            $site->forceFill(['id' => 194010 + $i, 'market_id' => $market->id, 'code' => 'ca016-'.$status, 'name' => 'Registry '.$status, 'domain' => 'ca016-'.$status.'.test', 'mode' => 'multi_category', 'default_locale' => 'en-US', 'currency_code' => 'EUR', 'timezone' => 'UTC', 'status' => $status, 'created_at' => $at, 'updated_at' => $at])->saveOrFail();
        }
        foreach (['Connection' => 'ca016_connection', 'Wireless' => 'ca016_wireless'] as $i => $code) {
            $definition = new AttributeDefinition;
            $definition->forceFill(['code' => $code, 'name' => $i, 'data_type' => 'string', 'created_at' => $at, 'updated_at' => $at])->saveOrFail();
        }
        $definitions = AttributeDefinition::query()->whereIn('code', ['ca016_connection', 'ca016_wireless'])->orderBy('code')->get();
        $names = ['Cameras', 'Coffee Machines', 'Electronics', 'Displays', 'Gaming Monitors', 'Esports Monitors', 'Keyboards', 'Mice', 'Office Monitors', 'Smartphones', 'Speakers', 'Tablets', 'Televisions', 'Wearables', 'Vintage Audio', 'Webcams', 'Accessories', 'Batteries', 'Chargers', 'Drones', 'Headphones', 'Laptops', 'Microphones', 'Printers', 'Routers'];
        $parents = [4 => 3, 5 => 4, 6 => 5, 9 => 4];
        $lifecycle = ['active', 'draft', 'active', 'active', 'active', 'draft', 'active', 'draft', 'active', 'active', 'draft', 'active', 'active', 'draft', 'archived'];
        $schema = ['approved', 'draft', 'reviewed', 'approved', 'draft', 'reviewed', 'approved', 'draft', 'draft', 'approved', 'draft', 'reviewed', 'approved', 'draft', 'archived'];
        $positions = [];
        foreach ($names as $index => $name) {
            $n = $index + 1;
            $scope = $parents[$n] ?? 0;
            $position = $positions[$scope] ?? 0;
            $positions[$scope] = $position + 1;
            $category = new CentralCategory;
            $category->forceFill(['id' => 194000 + $n, 'name' => $name, 'slug' => 'registry-'.Str::slug($name), 'parent_id' => isset($parents[$n]) ? 194000 + $parents[$n] : null, 'position' => $position, 'status' => $lifecycle[$index] ?? ($index % 3 === 0 ? 'draft' : 'active'), 'schema_status' => $schema[$index] ?? ($index % 3 === 0 ? 'draft' : 'reviewed'), 'schema_revision' => 1, 'schema_reviewed_revision' => in_array($schema[$index] ?? 'reviewed', ['reviewed', 'approved']) ? 1 : null, 'schema_approved_revision' => ($schema[$index] ?? '') === 'approved' ? 1 : null, 'created_at' => $at->subMonths(3), 'updated_at' => $at->subDays($index)])->saveOrFail();
            if (in_array($n, [1, 3, 4, 5, 6, 7], true)) {
                $section = new AttributeSection;
                $section->forceFill(['central_category_id' => $category->id, 'code' => 'specifications', 'name' => 'Specifications', 'position' => 0, 'is_visible' => true, 'is_collapsible' => false, 'created_at' => $at, 'updated_at' => $at])->saveOrFail();
                foreach ($definitions as $position => $definition) {
                    $assignment = new CategoryAttributeAssignment;
                    $assignment->forceFill(['central_category_id' => $category->id, 'attribute_definition_id' => $definition->id, 'attribute_section_id' => $position === 0 ? $section->id : null, 'position' => $position, 'is_required' => false, 'is_visible' => true, 'is_searchable' => false, 'is_sortable' => false, 'created_at' => $at, 'updated_at' => $at])->saveOrFail();
                }
                $facet = new FacetDefinition;
                $facet->forceFill(['category_id' => $category->id, 'code' => 'brand', 'source_type' => 'brand', 'facet_type' => 'checkbox', 'is_active' => true, 'is_filterable' => true, 'is_visible' => true, 'is_collapsible' => false, 'default_collapsed' => false, 'position' => 0, 'created_at' => $at, 'updated_at' => $at])->saveOrFail();
                foreach ([194010, 194011, 194012, 194013] as $siteId) {
                    $selection = new SiteCategory;
                    $selection->forceFill(['site_id' => $siteId, 'central_category_id' => $category->id, 'is_enabled' => $siteId !== 194010, 'local_status' => $siteId === 194010 ? 'hidden' : null, 'position' => $index, 'created_at' => $at, 'updated_at' => $at])->saveOrFail();
                }
            }
            $productCount = [1 => 1, 3 => 2, 4 => 3, 5 => 4, 6 => 1, 7 => 5][$n] ?? 0;
            for ($productN = 1; $productN <= $productCount; $productN++) {
                $product = new CentralProduct;
                $product->forceFill(['central_category_id' => $category->id, 'name' => $name.' Product '.$productN, 'model' => 'CA016-'.$n.'-'.$productN, 'slug' => 'ca016-'.$n.'-'.$productN, 'status' => $productN === 1 ? 'archived' : 'active', 'version' => 1, 'created_at' => $at, 'updated_at' => $at])->saveOrFail();
            }
            if ($n === 2) {
                continue;
            }
            foreach ($locales as $localeIndex => $locale) {
                $translation = new CategoryTranslation;
                $translation->forceFill(['category_id' => $category->id, 'locale_id' => $locale->id, 'locale' => $locale->code, 'name' => $name.' '.$locale->code, 'status' => $n === 5 && $localeIndex === 0 ? 'missing' : ($n === 6 && $localeIndex === 0 ? 'outdated' : 'human_reviewed'), 'created_at' => $at, 'updated_at' => $at])->saveOrFail();
            }
        }
    }
}

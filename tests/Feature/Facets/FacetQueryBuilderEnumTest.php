<?php

namespace Tests\Feature\Facets;

use App\Data\Facets\FacetFilterSet;
use App\Enums\AttributeDataType;
use App\Enums\FacetSourceType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use App\Services\Facets\FacetQueryBuilder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacetQueryBuilderEnumTest extends TestCase
{
    use RefreshDatabase;

    public function test_combines_enum_values_with_or_and_different_facets_with_and(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $this->configureEnumFacet($category, 'panel_type');
        $ips = $this->document($site, $category, ['brand_slug' => 'lg', 'panel_type' => 'ips']);
        $oled = $this->document($site, $category, ['brand_slug' => 'lg', 'panel_type' => 'oled']);
        $this->document($site, $category, ['brand_slug' => 'samsung', 'panel_type' => 'ips']);
        $this->document($site, $category, ['brand_slug' => 'lg', 'panel_type' => 'va']);

        $filters = FacetFilterSet::fromArray([
            'brand' => ['lg'],
            'panel_type' => ['ips', 'oled'],
        ]);
        $results = app(FacetQueryBuilder::class)
            ->apply(SiteSearchDocument::query(), $site, $category, $filters)
            ->get();

        $this->assertEqualsCanonicalizing([$ips->id, $oled->id], $results->pluck('id')->all());
    }

    public function test_filters_multi_enum_array_values(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $this->configureEnumFacet($category, 'ports', AttributeDataType::MultiEnum);
        $matching = $this->document($site, $category, ['ports' => ['hdmi', 'usb_c']]);
        $this->document($site, $category, ['ports' => ['displayport']]);

        $results = app(FacetQueryBuilder::class)->apply(
            SiteSearchDocument::query(),
            $site,
            $category,
            FacetFilterSet::fromArray(['ports' => ['usb_c']]),
        )->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($matching));
    }

    public function test_ignores_unknown_enum_filter_key(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $document = $this->document($site, $category, ['panel_type' => 'ips']);

        $results = app(FacetQueryBuilder::class)->apply(
            SiteSearchDocument::query(),
            $site,
            $category,
            FacetFilterSet::fromArray(['unknown_facet' => ['value']]),
        )->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($document));
    }

    private function configureEnumFacet(
        CentralCategory $category,
        string $code,
        AttributeDataType $dataType = AttributeDataType::Enum,
    ): void {
        $attribute = AttributeDefinition::factory()->for($category, 'category')->create([
            'code' => $code,
            'data_type' => $dataType,
        ]);
        FacetDefinition::factory()->for($category, 'category')->checkbox()->create([
            'attribute_definition_id' => $attribute->id,
            'source_type' => FacetSourceType::Attribute,
            'code' => $code,
        ]);
    }

    /** @param array<string, mixed> $values */
    private function document(Site $site, CentralCategory $category, array $values): SiteSearchDocument
    {
        return SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'filter_values_json' => [
                'category_id' => $category->id,
                ...$values,
            ],
        ]);
    }
}

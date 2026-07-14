<?php

namespace Tests\Feature\Facets;

use App\Data\Facets\FacetFilterSet;
use App\Enums\AttributeDataType;
use App\Enums\FacetSourceType;
use App\Enums\FacetType;
use App\Models\CentralCatalog\AttributeDefinition;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use App\Services\Facets\FacetQueryBuilder;
use App\Support\Facets\NumericRangeFacetParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacetQueryBuilderRangeTest extends TestCase
{
    use RefreshDatabase;

    public function test_numeric_parser_swaps_reversed_bounds_and_serializes_numbers(): void
    {
        $parser = new NumericRangeFacetParser;

        $this->assertSame(['min' => 120.0, 'max' => 240.0], $parser->parse('240', '120'));
        $this->assertSame(['min' => null, 'max' => 144.5], $parser->parse('invalid', '144.5'));
        $this->assertNull($parser->parse('invalid', null));
        $this->assertSame('144.5', $parser->serialize(144.5));
        $this->assertSame('144', $parser->serialize(144.0));
    }

    public function test_filters_by_minimum_maximum_and_combined_numeric_range(): void
    {
        [$site, $category] = $this->scenario();

        $cases = [
            [['refresh_rate_min' => 120], [144, 240]],
            [['refresh_rate_max' => 144], [60, 144]],
            [['refresh_rate_min' => 100, 'refresh_rate_max' => 200], [144]],
            [['refresh_rate_min' => 200, 'refresh_rate_max' => 100], [144]],
        ];

        foreach ($cases as [$input, $expected]) {
            $results = app(FacetQueryBuilder::class)->apply(
                SiteSearchDocument::query(),
                $site,
                $category,
                FacetFilterSet::fromArray($input),
            )->get();

            $this->assertEqualsCanonicalizing(
                $expected,
                $results->pluck('filter_values_json')->pluck('refresh_rate')->all(),
            );
        }
    }

    public function test_invalid_numeric_bounds_are_ignored_safely(): void
    {
        [$site, $category] = $this->scenario();

        $results = app(FacetQueryBuilder::class)->apply(
            SiteSearchDocument::query(),
            $site,
            $category,
            FacetFilterSet::fromArray([
                'refresh_rate_min' => 'fast',
                'refresh_rate_max' => 'faster',
            ]),
        )->get();

        $this->assertCount(3, $results);
    }

    /** @return array{Site, CentralCategory} */
    private function scenario(): array
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $attribute = AttributeDefinition::factory()->for($category, 'category')->create([
            'code' => 'refresh_rate',
            'data_type' => AttributeDataType::Decimal,
        ]);
        FacetDefinition::factory()->for($category, 'category')->create([
            'attribute_definition_id' => $attribute->id,
            'code' => 'refresh_rate',
            'source_type' => FacetSourceType::Attribute,
            'facet_type' => FacetType::Range,
        ]);

        foreach ([60, 144, 240] as $refreshRate) {
            SiteSearchDocument::factory()->create([
                'site_id' => $site->id,
                'filter_values_json' => [
                    'category_id' => $category->id,
                    'refresh_rate' => $refreshRate,
                ],
            ]);
        }

        return [$site, $category];
    }
}

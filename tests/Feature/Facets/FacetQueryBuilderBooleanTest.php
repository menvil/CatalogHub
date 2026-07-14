<?php

namespace Tests\Feature\Facets;

use App\Data\Facets\FacetFilterSet;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use App\Services\Facets\FacetQueryBuilder;
use App\Support\Facets\BooleanFacetValueParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FacetQueryBuilderBooleanTest extends TestCase
{
    use RefreshDatabase;

    public function test_boolean_parser_accepts_aliases_and_serializes_canonical_values(): void
    {
        $parser = new BooleanFacetValueParser;

        foreach ([true, 1, '1', 'true', 'yes'] as $value) {
            $this->assertTrue($parser->parse($value));
        }
        foreach ([false, 0, '0', 'false', 'no'] as $value) {
            $this->assertFalse($parser->parse($value));
        }
        $this->assertNull($parser->parse('sometimes'));
        $this->assertSame('1', $parser->serialize(true));
        $this->assertSame('0', $parser->serialize(false));
    }

    public function test_filters_search_documents_by_true_and_false_boolean_values(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        FacetDefinition::factory()->for($category, 'category')->boolean()->create(['code' => 'curved']);
        $curved = $this->document($site, $category, true);
        $flat = $this->document($site, $category, false);

        foreach ([['yes', $curved], ['0', $flat]] as [$value, $expected]) {
            $filters = FacetFilterSet::fromArray(['curved' => $value]);
            $results = app(FacetQueryBuilder::class)
                ->apply(SiteSearchDocument::query(), $site, $category, $filters)
                ->get();

            $this->assertCount(1, $results);
            $this->assertTrue($results->first()->is($expected));
            $this->assertContains(
                $value === 'yes' ? '1' : '0',
                collect($filters->appliedFilters())->pluck('value')->all(),
            );
        }
    }

    public function test_invalid_boolean_value_is_ignored(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        FacetDefinition::factory()->for($category, 'category')->boolean()->create(['code' => 'curved']);
        $this->document($site, $category, true);
        $this->document($site, $category, false);

        $results = app(FacetQueryBuilder::class)->apply(
            SiteSearchDocument::query(),
            $site,
            $category,
            FacetFilterSet::fromArray(['curved' => 'invalid']),
        )->get();

        $this->assertCount(2, $results);
    }

    private function document(Site $site, CentralCategory $category, bool $curved): SiteSearchDocument
    {
        return SiteSearchDocument::factory()->create([
            'site_id' => $site->id,
            'filter_values_json' => [
                'category_id' => $category->id,
                'curved' => $curved,
            ],
        ]);
    }
}

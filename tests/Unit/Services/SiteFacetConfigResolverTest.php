<?php

namespace Tests\Unit\Services;

use App\Models\CentralCatalog\CentralCategory;
use App\Models\FacetDefinition;
use App\Models\Site;
use App\Models\SiteFacetOverride;
use App\Services\Facets\SiteFacetConfigResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SiteFacetConfigResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_can_hide_category_facet(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $facet = FacetDefinition::factory()->for($category, 'category')->active()->create();
        SiteFacetOverride::factory()->for($site)->for($facet)->create(['is_visible' => false]);

        $facets = app(SiteFacetConfigResolver::class)->resolve($site, $category);

        $this->assertCount(0, $facets);
    }

    public function test_site_can_reorder_and_relabel_category_facets(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        $first = FacetDefinition::factory()->for($category, 'category')->active()->create([
            'code' => 'brand',
            'position' => 10,
            'config_json' => ['searchable' => false],
        ]);
        $second = FacetDefinition::factory()->for($category, 'category')->active()->create([
            'code' => 'panel_type',
            'position' => 20,
        ]);
        SiteFacetOverride::factory()->for($site)->for($second)->create([
            'label_override' => 'Display',
            'position_override' => 5,
            'default_collapsed' => true,
            'config_json' => ['searchable' => true],
        ]);

        $facets = app(SiteFacetConfigResolver::class)->resolve($site, $category);

        $this->assertSame($second->id, $facets->first()->id);
        $this->assertSame($first->id, $facets->last()->id);
        $this->assertSame('Display', $facets->first()->label);
        $this->assertTrue($facets->first()->defaultCollapsed);
        $this->assertTrue($facets->first()->config['searchable']);
    }

    public function test_empty_category_config_skips_site_override_query(): void
    {
        $site = Site::factory()->create();
        $category = CentralCategory::factory()->create();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $facets = app(SiteFacetConfigResolver::class)->resolve($site, $category);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertTrue($facets->isEmpty());
        $this->assertFalse(collect($queries)->contains(
            fn (array $query): bool => str_contains($query['query'], 'site_facet_overrides'),
        ));
    }
}

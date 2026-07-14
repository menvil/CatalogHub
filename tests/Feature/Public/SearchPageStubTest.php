<?php

namespace Tests\Feature\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchPageStubTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_page_renders_input_and_basic_projected_results(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        SiteSearchDocument::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'document_type' => 'product',
            'document_id' => 101,
            'title' => 'Aurora 27 Pro',
            'slug' => 'aurora-27-pro',
            'status' => ProjectionStatus::Active,
            'search_text' => 'Aurora 27 Pro 4K monitor',
            'payload_json' => ['summary' => ['key_specs' => ['4K', '165 Hz']]],
        ]);
        SiteSearchDocument::query()->create([
            'site_id' => $site->id,
            'locale' => 'de-DE',
            'document_type' => 'product',
            'document_id' => 102,
            'title' => 'Aurora German',
            'slug' => 'aurora-de',
            'status' => ProjectionStatus::Active,
            'search_text' => 'Aurora monitor',
        ]);

        $this->get('http://tech-compare.test/en-US/search?q=aurora')
            ->assertOk()
            ->assertSee('Search the catalogue')
            ->assertSee('value="aurora"', false)
            ->assertSee('Aurora 27 Pro')
            ->assertDontSee('Aurora German')
            ->assertSee('/en-US/products/aurora-27-pro', false);
    }

    public function test_search_page_renders_a_no_results_container(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);

        $this->get('http://tech-compare.test/en-US/search?q=absent')
            ->assertOk()
            ->assertSee('No projected products matched your search');
    }

    public function test_search_treats_like_wildcards_and_the_escape_character_literally(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);
        $site = Site::query()->where('code', 'tech-compare-global')->firstOrFail();
        SiteSearchDocument::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'document_type' => 'product',
            'document_id' => 201,
            'title' => 'Literal symbols',
            'slug' => 'literal-symbols',
            'status' => ProjectionStatus::Active,
            'search_text' => 'Panel 100%_! ready',
        ]);
        SiteSearchDocument::query()->create([
            'site_id' => $site->id,
            'locale' => 'en-US',
            'document_type' => 'product',
            'document_id' => 202,
            'title' => 'Wildcard candidate',
            'slug' => 'wildcard-candidate',
            'status' => ProjectionStatus::Active,
            'search_text' => 'Panel 1000A! ready',
        ]);

        $this->get('http://tech-compare.test/en-US/search?q='.urlencode('%_!'))
            ->assertOk()
            ->assertSee('Literal symbols')
            ->assertDontSee('Wildcard candidate');
    }
}

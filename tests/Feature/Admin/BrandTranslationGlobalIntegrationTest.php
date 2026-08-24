<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Translations\SaveBrandTranslationAction;
use App\Data\Translations\BrandTranslationInput;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Models\User;
use App\Queries\Translations\MissingTranslationsQuery;
use App\Queries\Translations\OutdatedTranslationsQuery;
use App\Services\Translations\TranslationCompletenessService;
use App\Services\Translations\TranslationStatsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class BrandTranslationGlobalIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_missing_query_and_entity_filter_link_brand_to_ca_015_until_saved(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Samsung']);
        $locale = Locale::factory()->create(['code' => 'de-DE', 'is_active' => true]);

        $items = app(MissingTranslationsQuery::class)->get(locale: $locale->code, entityType: 'brand');
        $item = collect($items)->firstWhere('entity_id', $brand->id);

        $this->assertIsArray($item);
        $this->assertSame('brand', $item['entity_type']);
        $this->assertSame('Samsung', $item['source_label']);
        $this->assertSame('de-DE', $item['locale']);
        $this->assertSame(route('central.brands.translations.edit', [$brand, $locale->code]), $item['editor_url']);

        app(SaveBrandTranslationAction::class)->handle($brand, $locale, $this->input());

        $remaining = app(MissingTranslationsQuery::class)->get(locale: $locale->code, entityType: 'brand');
        $this->assertNull(collect($remaining)->firstWhere('entity_id', $brand->id));

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(route('central.translations.missing', ['entity_type' => 'brand']))
            ->assertOk()
            ->assertDontSee('Samsung');
    }

    public function test_outdated_query_and_entity_filter_link_brand_to_ca_015(): void
    {
        $brand = CentralBrand::factory()->create(['name' => 'Samsung Electronics']);
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        BrandTranslation::factory()->outdated()->create([
            'brand_id' => $brand->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'name' => 'Samsung',
        ]);

        $items = app(OutdatedTranslationsQuery::class)->get(locale: $locale->code, entityType: 'brand');

        $this->assertCount(1, $items);
        $this->assertSame('brand', $items[0]['entity_type']);
        $this->assertSame('Samsung Electronics', $items[0]['source_label']);
        $this->assertSame('Samsung', $items[0]['translated_label']);
        $this->assertSame('de-DE', $items[0]['locale']);
        $this->assertSame(route('central.brands.translations.edit', [$brand, $locale->code]), $items[0]['editor_url']);

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(route('central.translations.outdated', ['entity_type' => 'brand']))
            ->assertOk()
            ->assertSee('Samsung Electronics')
            ->assertSee('Samsung');
    }

    public function test_missing_brand_search_treats_like_metacharacters_as_literals(): void
    {
        $matching = CentralBrand::factory()->create(['name' => 'Samsung 100%_safe!']);
        $wildcardOnly = CentralBrand::factory()->create(['name' => 'Samsung 100XxsafeX']);
        $locale = Locale::factory()->create(['code' => 'de-DE', 'is_active' => true]);

        $items = app(MissingTranslationsQuery::class)->get(
            locale: $locale->code,
            entityType: 'brand',
            search: '100%_safe!',
        );
        $entityIds = collect($items)->pluck('entity_id');

        $this->assertTrue($entityIds->contains($matching->id));
        $this->assertFalse($entityIds->contains($wildcardOnly->id));
    }

    public function test_completeness_and_dashboard_totals_include_brands_without_regressing_existing_sections(): void
    {
        Locale::query()->update(['is_active' => false, 'is_default' => false]);
        $locale = Locale::factory()->create(['code' => 'de-DE', 'is_active' => true]);
        $before = app(TranslationCompletenessService::class)->forLocale($locale->code);
        $dashboardBefore = app(TranslationStatsService::class)->dashboard();
        $brands = CentralBrand::factory()->count(3)->create();
        BrandTranslation::factory()->approved()->create([
            'brand_id' => $brands[0]->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
        ]);
        BrandTranslation::factory()->outdated()->create([
            'brand_id' => $brands[1]->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
        ]);

        $stats = app(TranslationCompletenessService::class)->forLocale($locale->code);

        $this->assertSame(
            ['brands', 'products', 'categories', 'attributes', 'sections', 'options', 'units'],
            array_keys($stats['by_entity']),
        );
        $this->assertSame([
            'required' => $before['by_entity']['brands']['required'] + 3,
            'approved' => $before['by_entity']['brands']['approved'] + 1,
            'missing' => $before['by_entity']['brands']['missing'] + 1,
            'outdated' => $before['by_entity']['brands']['outdated'] + 1,
            'coverage' => round(
                (($before['by_entity']['brands']['approved'] + 1) / ($before['by_entity']['brands']['required'] + 3)) * 100,
                1,
            ),
        ], $stats['by_entity']['brands']);
        $this->assertSame($before['required'] + 3, $stats['required']);
        $this->assertSame($before['approved'] + 1, $stats['approved']);
        $this->assertSame($before['missing'] + 1, $stats['missing']);
        $this->assertSame($before['outdated'] + 1, $stats['outdated']);

        Cache::flush();
        $dashboard = app(TranslationStatsService::class)->dashboard();
        $this->assertSame($dashboardBefore['approved_count'] + 1, $dashboard['approved_count']);
        $this->assertSame($dashboardBefore['outdated_count'] + 1, $dashboard['outdated_count']);
        $this->assertSame($dashboardBefore['missing_count'] + 1, $dashboard['missing_count']);
        $this->assertSame($stats['coverage'], $dashboard['coverage_by_locale'][0]['coverage']);
    }

    public function test_invalid_entity_filters_still_fail_safely(): void
    {
        $admin = User::factory()->centralAdmin()->create();

        $this->actingAs($admin)
            ->get(route('central.translations.missing', ['entity_type' => 'unknown']))
            ->assertSessionHasErrors('entity_type');
        $this->get(route('central.translations.outdated', ['entity_type' => 'unknown']))
            ->assertSessionHasErrors('entity_type');
    }

    private function input(): BrandTranslationInput
    {
        return new BrandTranslationInput(
            name: 'Samsung',
            tagline: null,
            shortDescription: null,
            description: null,
            seoTitle: null,
            seoDescription: null,
            status: TranslationStatus::HumanReviewed,
        );
    }
}

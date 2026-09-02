<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Data\CentralCatalog\BrandListFiltersData;
use App\Enums\CentralBrandQualityState;
use App\Enums\CentralBrandStatus;
use App\Enums\CentralProductStatus;
use App\Enums\MediaDeliveryState;
use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralBrandOwnership;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Locale;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\Organization;
use App\Models\Translations\BrandTranslation;
use App\Queries\CentralCatalog\CentralBrandListReadModelQuery;
use App\Queries\CentralCatalog\CentralBrandQualityQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CountryReference;
use Tests\Support\DatabaseQueryCounter;
use Tests\TestCase;

final class CentralBrandListReadModelQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_kpis_and_page_rows_are_derived_from_authoritative_persisted_state(): void
    {
        Storage::fake('public');
        $locale = Locale::factory()->create(['code' => 'en-US']);
        $complete = $this->completeBrand(['name' => 'Complete Brand', 'slug' => 'complete-brand']);
        $missing = CentralBrand::factory()->active()->create(['name' => 'Missing Brand', 'slug' => 'missing-brand']);
        $outdated = $this->completeBrand([
            'name' => 'Outdated Brand',
            'slug' => 'outdated-brand',
            'status' => CentralBrandStatus::Archived,
        ]);
        $this->assignUsableLogo($complete);
        BrandTranslation::factory()->create([
            'brand_id' => $complete->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
            'status' => TranslationStatus::Approved,
        ]);
        BrandTranslation::factory()->outdated()->create([
            'brand_id' => $outdated->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
        ]);
        $categoryA = CentralCategory::factory()->create();
        $categoryB = CentralCategory::factory()->create();
        CentralProduct::factory()->for($complete, 'brand')->create(['central_category_id' => $categoryA->id]);
        CentralProduct::factory()->for($complete, 'brand')->create(['central_category_id' => $categoryB->id]);
        CentralProduct::factory()->for($complete, 'brand')->create([
            'central_category_id' => $categoryB->id,
            'status' => CentralProductStatus::Archived,
        ]);

        $list = app(CentralBrandListReadModelQuery::class)->paginate($this->filters());

        self::assertSame(3, $list->summary->total);
        self::assertSame(2, $list->summary->active);
        self::assertSame(1, $list->summary->withLogos);
        self::assertSame(1, $list->summary->missingTranslations);
        self::assertSame(2, $list->summary->needsAttention);
        self::assertSame(66.7, $list->summary->percentage($list->summary->active));

        $completeRow = $list->brands->getCollection()->first(
            static fn ($row): bool => $row->brand->is($complete),
        );
        self::assertNotNull($completeRow);
        self::assertSame(2, $completeRow->brand->products_count);
        self::assertSame(2, $completeRow->categoryCount);
        self::assertSame(100, $completeRow->health->translations->score());
        self::assertSame(MediaDeliveryState::Ready, $completeRow->health->logo->state);
        self::assertSame(CentralBrandQualityState::Complete, $completeRow->health->summary->state);

        $detailQuality = app(CentralBrandQualityQuery::class)->forBrand($complete);
        self::assertSame($detailQuality->summary->state, $completeRow->health->summary->state);
        self::assertSame($detailQuality->summary->score, $completeRow->health->summary->score);
        self::assertSame($detailQuality->summary->issueCodes(), $completeRow->health->summary->issueCodes());

        $query = app(CentralBrandListReadModelQuery::class);
        self::assertSame([$outdated->id], $query->paginate($this->filters(translation: 'outdated'))->brands
            ->getCollection()->map(fn ($row): int => (int) $row->brand->id)->all());
        self::assertEqualsCanonicalizing([$missing->id, $outdated->id], $query
            ->paginate($this->filters(translation: 'needs_attention'))->brands
            ->getCollection()->map(fn ($row): int => (int) $row->brand->id)->all());
    }

    public function test_translation_quality_and_combined_domain_filters_use_the_same_health_projection(): void
    {
        Storage::fake('public');
        $locale = Locale::factory()->create(['code' => 'en-US']);
        $complete = $this->completeBrand([
            'name' => 'Acme Complete',
            'slug' => 'acme-complete',
            'country_id' => CountryReference::id('DE'),
        ]);
        $missing = CentralBrand::factory()->draft()->create([
            'name' => 'Acme Missing',
            'slug' => 'acme-missing',
            'country_id' => CountryReference::id('US'),
        ]);
        $category = CentralCategory::factory()->create();
        CentralProduct::factory()->for($complete, 'brand')->create(['central_category_id' => $category->id]);
        $this->assignUsableLogo($complete);
        BrandTranslation::factory()->humanReviewed()->create([
            'brand_id' => $complete->id,
            'locale_id' => $locale->id,
            'locale' => $locale->code,
        ]);
        $organization = Organization::factory()->create(['name' => 'Acme Holdings']);
        CentralBrandOwnership::factory()->create([
            'central_brand_id' => $complete->id,
            'organization_id' => $organization->id,
        ]);
        $query = app(CentralBrandListReadModelQuery::class);

        self::assertSame([$complete->id], $query->paginate($this->filters(
            search: 'Acme Holdings',
            status: CentralBrandStatus::Active->value,
            countryId: CountryReference::id('DE'),
            coverage: 'has',
            translation: 'complete',
            quality: 'complete',
        ))->brands->getCollection()->map(fn ($row): int => (int) $row->brand->id)->all());
        self::assertSame([$missing->id], $query->paginate($this->filters(translation: 'missing'))->brands
            ->getCollection()->map(fn ($row): int => (int) $row->brand->id)->all());
        self::assertSame([$missing->id], $query->paginate($this->filters(quality: 'needs_attention'))->brands
            ->getCollection()->map(fn ($row): int => (int) $row->brand->id)->all());
        self::assertSame([$missing->id], $query->paginate($this->filters(coverage: 'none'))->brands
            ->getCollection()->map(fn ($row): int => (int) $row->brand->id)->all());
    }

    public function test_query_count_does_not_grow_per_brand(): void
    {
        Storage::fake('public');
        Locale::factory()->create(['code' => 'en-US']);
        CentralBrand::factory()->create();
        $query = app(CentralBrandListReadModelQuery::class);

        $one = DatabaseQueryCounter::measure(fn () => $query->paginate($this->filters()));

        CentralBrand::factory()->count(19)->create();
        $twenty = DatabaseQueryCounter::measure(fn () => $query->paginate($this->filters()));

        self::assertSame($one['count'], $twenty['count']);
        self::assertLessThanOrEqual(12, $twenty['count']);
        self::assertCount(20, $twenty['result']->brands->items());
    }

    public function test_zero_brand_summary_has_no_percentage(): void
    {
        $summary = app(CentralBrandListReadModelQuery::class)->paginate($this->filters())->summary;

        self::assertSame(0, $summary->total);
        self::assertSame(0, $summary->active);
        self::assertSame(0, $summary->withLogos);
        self::assertSame(0, $summary->missingTranslations);
        self::assertSame(0, $summary->needsAttention);
        self::assertNull($summary->percentage(0));
    }

    private function completeBrand(array $overrides = []): CentralBrand
    {
        return CentralBrand::factory()->active()->create([
            'website_url' => 'https://example.test',
            'country_id' => CountryReference::id('DE'),
            'founded_year' => 2001,
            'support_url' => 'https://example.test/support',
            'primary_color' => '#123456',
            ...$overrides,
        ]);
    }

    private function assignUsableLogo(CentralBrand $brand): void
    {
        $path = 'media/originals/'.$brand->slug.'.png';
        Storage::disk('public')->put($path, 'usable logo '.$brand->slug);
        $asset = MediaAsset::factory()->create(['disk' => 'public', 'original_path' => $path]);
        MediaAssignment::factory()->for($asset, 'asset')->create([
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $brand->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
            'visibility' => 'global',
        ]);
    }

    private function filters(
        ?string $search = null,
        ?string $status = null,
        ?int $countryId = null,
        ?string $coverage = null,
        ?string $translation = null,
        ?string $quality = null,
    ): BrandListFiltersData {
        return new BrandListFiltersData(
            search: $search,
            status: $status,
            countryId: $countryId,
            categoryCoverage: $coverage,
            translation: $translation,
            quality: $quality,
            sort: 'name',
            direction: 'asc',
            perPage: 20,
        );
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Queries;

use App\Enums\CentralBrandQualityState;
use App\Enums\CentralBrandStatus;
use App\Models\AuditLogEntry;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\MediaVariant;
use App\Models\Translations\BrandTranslation;
use App\Queries\CentralCatalog\CentralBrandQualityQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CountryReference;
use Tests\Support\DatabaseQueryCounter;
use Tests\TestCase;

final class CentralBrandQualityQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_builds_a_complete_summary_from_canonical_media_and_active_translation_data(): void
    {
        Storage::fake('public');
        $brand = $this->completeBrand();
        $active = Locale::factory()->create(['code' => 'de-DE']);
        $inactive = Locale::factory()->disabled()->create(['code' => 'fr-FR']);
        BrandTranslation::factory()->approved()->create([
            'brand_id' => $brand->id,
            'locale_id' => $active->id,
            'locale' => $active->code,
        ]);
        BrandTranslation::factory()->outdated()->create([
            'brand_id' => $brand->id,
            'locale_id' => $inactive->id,
            'locale' => $inactive->code,
        ]);
        $this->assignUsableLogo($brand);

        $result = app(CentralBrandQualityQuery::class)->forBrand($brand);

        self::assertSame(CentralBrandQualityState::Complete, $result->summary->state);
        self::assertSame(100, $result->summary->score);
        self::assertSame(7, $result->summary->completedChecks);
        self::assertSame(7, $result->summary->totalChecks);
        self::assertSame([], $result->summary->issueCodes());
        self::assertNotNull($result->logo->url);
    }

    public function test_unavailable_assigned_logo_is_distinct_from_a_missing_logo(): void
    {
        Storage::fake('public');
        $brand = $this->completeBrand();
        $asset = MediaAsset::factory()->create([
            'disk' => 'public',
            'original_path' => 'media/originals/unavailable.png',
        ]);
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

        $unusable = app(CentralBrandQualityQuery::class)->forBrand($brand)->summary;
        $asset->assignments()->delete();
        $missing = app(CentralBrandQualityQuery::class)->forBrand($brand)->summary;

        self::assertContains('brand_logo_unusable', $unusable->issueCodes());
        self::assertNotContains('brand_logo_missing', $unusable->issueCodes());
        self::assertContains('brand_logo_missing', $missing->issueCodes());
    }

    public function test_eager_loaded_ready_variant_makes_logo_usable_without_a_master_file(): void
    {
        Storage::fake('public');
        $brand = $this->completeBrand();
        $asset = MediaAsset::factory()->create([
            'disk' => 'public',
            'original_path' => 'media/originals/unavailable-master.png',
        ]);
        $variantPath = 'media/variants/quality-logo/brand_logo_256.webp';
        MediaVariant::factory()->for($asset, 'asset')->create([
            'variant_type' => 'brand_logo_256',
            'disk' => 'public',
            'path' => $variantPath,
            'status' => 'ready',
        ]);
        Storage::disk('public')->put($variantPath, 'usable variant');
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

        $result = app(CentralBrandQualityQuery::class)->forBrand($brand);

        self::assertSame('brand_logo_256', $result->logo->variantName);
        self::assertSame(CentralBrandQualityState::Complete, $result->summary->state);
        self::assertSame(100, $result->summary->score);
    }

    public function test_non_global_assignment_does_not_satisfy_the_global_primary_logo_check(): void
    {
        Storage::fake('public');
        $brand = $this->completeBrand();
        $path = 'media/originals/private-logo.png';
        Storage::disk('public')->put($path, 'private logo');
        $asset = MediaAsset::factory()->create(['disk' => 'public', 'original_path' => $path]);
        MediaAssignment::factory()->for($asset, 'asset')->create([
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $brand->id,
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
            'visibility' => 'private',
        ]);

        $result = app(CentralBrandQualityQuery::class)->forBrand($brand);

        self::assertNull($result->logo->url);
        self::assertContains('brand_logo_missing', $result->summary->issueCodes());
        self::assertNotContains('brand_logo_unusable', $result->summary->issueCodes());
    }

    public function test_read_model_has_no_side_effects_and_does_not_change_lifecycle(): void
    {
        Storage::fake('public');
        $brand = CentralBrand::factory()->archived()->create([
            'name' => 'Read Only Brand',
            'updated_at' => '2026-08-27 10:00:00',
        ]);
        $locale = Locale::factory()->create(['code' => 'de-DE']);
        $before = $brand->fresh()->getAttributes();
        $auditEntryCount = AuditLogEntry::query()->count();

        app(CentralBrandQualityQuery::class)->forBrand($brand);

        self::assertSame($before, $brand->fresh()->getAttributes());
        self::assertSame(CentralBrandStatus::Archived, $brand->fresh()->status);
        self::assertDatabaseCount('locales', 1);
        self::assertDatabaseHas('locales', ['id' => $locale->id]);
        self::assertDatabaseCount('brand_translations', 0);
        self::assertDatabaseCount('media_assignments', 0);
        self::assertSame($auditEntryCount, AuditLogEntry::query()->count());
    }

    public function test_query_count_is_bounded_when_active_locales_increase(): void
    {
        Storage::fake('public');
        $brand = $this->completeBrand();
        Locale::factory()->create(['code' => 'de-DE']);
        $this->assignUsableLogo($brand);
        $readModel = app(CentralBrandQualityQuery::class);

        $oneLocale = DatabaseQueryCounter::measure(fn () => $readModel->forBrand($brand));

        Locale::factory()->count(8)->create();
        $manyLocales = DatabaseQueryCounter::measure(fn () => $readModel->forBrand($brand));

        self::assertSame($oneLocale['count'], $manyLocales['count']);
        self::assertLessThanOrEqual(5, $manyLocales['count']);
    }

    private function completeBrand(): CentralBrand
    {
        return CentralBrand::factory()->active()->create([
            'website_url' => 'https://example.test',
            'country_id' => CountryReference::id('DE'),
            'founded_year' => 1999,
            'support_url' => 'https://example.test/support',
            'contact_email' => null,
            'primary_color' => '#123456',
        ]);
    }

    private function assignUsableLogo(CentralBrand $brand): void
    {
        $path = 'media/originals/quality-logo.png';
        Storage::disk('public')->put($path, 'usable logo');
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
}

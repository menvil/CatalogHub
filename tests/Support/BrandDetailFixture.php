<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CatalogTag;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralBrandOwnership;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Imports\CentralBrandExternalIdentity;
use App\Models\Imports\ImportSource;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\Organization;
use App\Support\Imports\ExternalIdentityNormalizer;
use App\Support\Normalization\OrganizationNameNormalizer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

final class BrandDetailFixture
{
    public const VERSION = 'brand-detail-v9';

    public const ACTIVE_BRAND_ID = 20;

    public const ARCHIVED_BRAND_ID = 21;

    public const DRAFT_BRAND_ID = 24;

    public const NEEDS_ATTENTION_BRAND_ID = self::ACTIVE_BRAND_ID;

    public const COMPLETE_BRAND_ID = self::ARCHIVED_BRAND_ID;

    public static function create(): void
    {
        $activeBrand = CentralBrand::query()->find(self::ACTIVE_BRAND_ID);
        $archivedBrand = CentralBrand::query()->find(self::ARCHIVED_BRAND_ID);
        $draftBrand = CentralBrand::query()->find(self::DRAFT_BRAND_ID);

        if (
            ! $activeBrand instanceof CentralBrand || $activeBrand->slug !== 'samsung'
            || ! $archivedBrand instanceof CentralBrand || $archivedBrand->slug !== 'sony'
            || ! $draftBrand instanceof CentralBrand || $draftBrand->slug !== 'zotac'
        ) {
            throw new RuntimeException('BrandDetailFixture requires the deterministic Samsung, Sony, and Zotac BrandListFixture records.');
        }

        $activeBrand->forceFill([
            'website_url' => 'https://www.samsung.com/',
            'country_id' => CountryReference::id('KR'),
            'founded_year' => 1938,
            'support_url' => null,
            'contact_email' => null,
            'primary_color' => '#1428A0',
            'updated_at' => CarbonImmutable::parse('2026-07-26T09:00:00Z'),
        ])->saveOrFail();

        $normalizedParentCompanyName = OrganizationNameNormalizer::search('Samsung Electronics Co., Ltd.');
        $parentCompany = new Organization;
        $parentCompany->forceFill([
            'id' => 120100,
            'name' => 'Samsung Electronics Co., Ltd.',
            'normalized_name' => $normalizedParentCompanyName,
            'normalized_name_prefix' => OrganizationNameNormalizer::prefixForNormalizedName($normalizedParentCompanyName),
            'created_at' => CarbonImmutable::parse('2026-08-12T10:00:00Z'),
            'updated_at' => CarbonImmutable::parse('2026-08-12T10:00:00Z'),
        ])->saveOrFail();

        $ownership = new CentralBrandOwnership;
        $ownership->forceFill([
            'id' => 120100,
            'central_brand_id' => $activeBrand->getKey(),
            'organization_id' => $parentCompany->getKey(),
            'created_at' => CarbonImmutable::parse('2026-08-12T10:00:00Z'),
            'updated_at' => CarbonImmutable::parse('2026-08-12T10:00:00Z'),
        ])->saveOrFail();

        $archivedBrand->forceFill([
            'website_url' => 'https://www.sony.com/',
            'country_id' => CountryReference::id('JP'),
            'founded_year' => 1946,
            'support_url' => 'https://www.sony.com/electronics/support',
            'contact_email' => 'catalog@sony.example',
            'primary_color' => '#000000',
            'updated_at' => CarbonImmutable::parse('2026-07-27T09:00:00Z'),
        ])->saveOrFail();

        $normalizedCompleteParentName = OrganizationNameNormalizer::search('Sony Group Corporation');
        $completeParentCompany = new Organization;
        $completeParentCompany->forceFill([
            'id' => 120106,
            'name' => 'Sony Group Corporation',
            'normalized_name' => $normalizedCompleteParentName,
            'normalized_name_prefix' => OrganizationNameNormalizer::prefixForNormalizedName($normalizedCompleteParentName),
            'created_at' => CarbonImmutable::parse('2026-08-12T10:00:00Z'),
            'updated_at' => CarbonImmutable::parse('2026-08-12T10:00:00Z'),
        ])->saveOrFail();

        $completeOwnership = new CentralBrandOwnership;
        $completeOwnership->forceFill([
            'id' => 120106,
            'central_brand_id' => $archivedBrand->getKey(),
            'organization_id' => $completeParentCompany->getKey(),
            'created_at' => CarbonImmutable::parse('2026-08-12T10:00:00Z'),
            'updated_at' => CarbonImmutable::parse('2026-08-12T10:00:00Z'),
        ])->saveOrFail();

        self::assignBrandLogo($activeBrand, 120109);
        self::assignBrandLogo($archivedBrand, 120110);

        $tags = collect(['Consumer Electronics', 'Innovation', 'Premium', 'Mobile', 'Display'])
            ->map(static fn (string $name): CatalogTag => CatalogTag::factory()->create(['name' => $name]));
        $activeBrand->tags()->attach($tags->pluck('id')->all());

        $manufacturerApi = new ImportSource;
        $manufacturerApi->forceFill([
            'id' => 120101,
            'code' => 'manufacturer_api',
            'name' => 'Manufacturer API',
            'type' => ImportSource::TYPE_API,
            'status' => 'active',
            'config_json' => ['token' => 'fixture-secret-must-never-render'],
            'description' => 'Deterministic Brand provenance fixture.',
        ])->saveOrFail();

        $legacyFeed = new ImportSource;
        $legacyFeed->forceFill([
            'id' => 120102,
            'code' => 'legacy_feed',
            'name' => 'Legacy Feed',
            'type' => ImportSource::TYPE_CSV,
            'status' => 'inactive',
            'config_json' => ['password' => 'fixture-secret-must-never-render'],
            'description' => 'Deterministic inactive provenance fixture.',
        ])->saveOrFail();

        foreach ([
            [120103, $manufacturerApi, 'brand-00142', 'https://example.test/brands/brand-00142'],
            [120104, $legacyFeed, 'SAMSUNG', null],
        ] as [$id, $source, $externalId, $externalUrl]) {
            $identity = new CentralBrandExternalIdentity;
            $identity->forceFill([
                'id' => $id,
                'central_brand_id' => $activeBrand->getKey(),
                'import_source_id' => $source->getKey(),
                'external_id' => $externalId,
                'external_id_hash' => ExternalIdentityNormalizer::hash($externalId),
                'external_url' => $externalUrl,
                'created_at' => CarbonImmutable::parse('2026-08-12T10:00:00Z'),
                'updated_at' => CarbonImmutable::parse('2026-08-12T10:00:00Z'),
            ])->saveOrFail();
        }

        $categories = collect([
            [120121, 'Smartphones', 'smartphones'],
            [120122, 'Televisions', 'televisions'],
            [120123, 'Tablets', 'tablets'],
            [120124, 'Laptops', 'laptops'],
            [120125, 'Monitors', 'monitors'],
        ])->mapWithKeys(function (array $record): array {
            [$id, $name, $slug] = $record;
            $category = CentralCategory::factory()->create(['id' => $id, 'name' => $name, 'slug' => $slug]);

            return [$slug => $category];
        });

        $products = [
            ['Samsung Galaxy S26 Ultra', 'SM-S948', 'samsung-galaxy-s26-ultra', 'smartphones', CentralProductStatus::Active, '2026-08-15T14:00:00Z'],
            ['Samsung Galaxy Z Fold 8', 'SM-F976', 'samsung-galaxy-z-fold-8', 'smartphones', CentralProductStatus::Active, '2026-08-15T13:15:00Z'],
            ['Samsung Galaxy Tab S12 Ultra', 'SM-X946', 'samsung-galaxy-tab-s12-ultra', 'tablets', CentralProductStatus::Active, '2026-08-15T11:30:00Z'],
            ['Samsung Neo QLED 8K QN990F', 'QN990F', 'samsung-neo-qled-8k-qn990f', 'televisions', CentralProductStatus::Draft, '2026-08-14T16:00:00Z'],
            ['Samsung Odyssey OLED G9', 'G95SD', 'samsung-odyssey-oled-g9', 'monitors', CentralProductStatus::Active, '2026-08-12T09:00:00Z'],
            ['Samsung OLED S95F', 'S95F', 'samsung-oled-s95f', 'televisions', CentralProductStatus::Active, '2026-08-10T15:45:00Z'],
            ['Samsung Galaxy Book5 Pro', 'NP960XHA', 'samsung-galaxy-book5-pro', 'laptops', CentralProductStatus::Active, '2026-08-08T10:20:00Z'],
            ['Samsung Galaxy Book5 Edge', 'NP940XMA', 'samsung-galaxy-book5-edge', 'laptops', CentralProductStatus::Active, '2026-08-06T08:10:00Z'],
            ['Samsung Galaxy Note 8', 'SM-N950', 'samsung-galaxy-note-8', 'smartphones', CentralProductStatus::Archived, '2026-08-16T09:00:00Z'],
        ];

        foreach ($products as $offset => [$name, $model, $slug, $categorySlug, $status, $updatedAt]) {
            $product = new CentralProduct;
            $product->forceFill([
                'id' => 1201201 + $offset,
                'central_brand_id' => $activeBrand->getKey(),
                'central_category_id' => $categories->get($categorySlug)?->getKey(),
                'name' => $name,
                'model' => $model,
                'slug' => $slug,
                'status' => $status,
                'version' => 1,
                'created_at' => CarbonImmutable::parse('2026-08-09T10:00:00Z'),
                'updated_at' => CarbonImmutable::parse($updatedAt),
            ])->saveOrFail();
        }
    }

    private static function assignBrandLogo(CentralBrand $brand, int $id): void
    {
        $timestamp = CarbonImmutable::parse('2026-08-12T10:00:00Z');
        $logoPath = 'media/originals/ca-012-'.$brand->slug.'-logo.png';
        $logoBytes = (string) file_get_contents(base_path('tests/Fixtures/media/brand-logo-a.png')).'CA012-'.$brand->slug;
        Storage::disk('public')->put($logoPath, $logoBytes);

        $asset = new MediaAsset;
        $asset->forceFill([
            'id' => $id,
            'uuid' => sprintf('00000000-0000-4000-8000-%012d', $id),
            'type' => 'image',
            'source' => 'fixture',
            'disk' => 'public',
            'original_path' => $logoPath,
            'original_filename' => $brand->slug.'-logo.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($logoBytes),
            'width' => 320,
            'height' => 160,
            'checksum' => 'sha256:'.hash('sha256', $logoBytes),
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveOrFail();

        $assignment = new MediaAssignment;
        $assignment->forceFill([
            'id' => $id,
            'media_asset_id' => $asset->getKey(),
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $brand->getKey(),
            'role' => MediaAssignment::ROLE_BRAND_LOGO,
            'position' => 0,
            'locale' => null,
            'site_id' => null,
            'market_id' => null,
            'is_primary' => true,
            'visibility' => 'global',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveOrFail();
    }

    private function __construct() {}
}

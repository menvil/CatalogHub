<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\CentralProductStatus;
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
    public const VERSION = 'brand-detail-v6';

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
            'support_url' => 'https://www.samsung.com/support/',
            'contact_email' => 'support@example.com',
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
            'contact_email' => null,
            'primary_color' => '#000000',
            'updated_at' => CarbonImmutable::parse('2026-07-27T09:00:00Z'),
        ])->saveOrFail();

        self::assignCompleteBrandLogo($archivedBrand);

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
        ])->mapWithKeys(function (array $record): array {
            [$id, $name, $slug] = $record;
            $category = CentralCategory::factory()->create(['id' => $id, 'name' => $name, 'slug' => $slug]);

            return [$slug => $category];
        });

        $products = [
            ['Samsung Galaxy S26', 'SM-S942', 'samsung-galaxy-s26', 'smartphones', CentralProductStatus::Active],
            ['Samsung Galaxy Tab S12', 'SM-X940', 'samsung-galaxy-tab-s12', 'tablets', CentralProductStatus::Active],
            ['Samsung Neo QLED TV', 'QN90F', 'samsung-neo-qled-tv', 'televisions', CentralProductStatus::Draft],
            ['Samsung Legacy Laptop', 'NP-OLD', 'samsung-legacy-laptop', 'laptops', CentralProductStatus::Archived],
        ];

        foreach ([['smartphones', 23], ['televisions', 11], ['tablets', 5]] as [$slug, $additional]) {
            for ($index = 1; $index <= $additional; $index++) {
                $products[] = [
                    "Samsung {$slug} fixture {$index}",
                    strtoupper(substr($slug, 0, 3)).'-'.$index,
                    "samsung-{$slug}-fixture-{$index}",
                    $slug,
                    CentralProductStatus::Active,
                ];
            }
        }

        foreach ($products as $offset => [$name, $model, $slug, $categorySlug, $status]) {
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
                'updated_at' => CarbonImmutable::parse('2026-08-09T10:00:00Z'),
            ])->saveOrFail();
        }
    }

    private static function assignCompleteBrandLogo(CentralBrand $brand): void
    {
        $timestamp = CarbonImmutable::parse('2026-08-12T10:00:00Z');
        $logoPath = 'media/originals/ca-012-complete-sony-logo.png';
        $logoBytes = (string) file_get_contents(base_path('tests/Fixtures/media/brand-logo-a.png')).'CA012-PHASE13';
        Storage::disk('public')->put($logoPath, $logoBytes);

        $asset = new MediaAsset;
        $asset->forceFill([
            'id' => 120110,
            'uuid' => '00000000-0000-4000-8000-000000120110',
            'type' => 'image',
            'source' => 'fixture',
            'disk' => 'public',
            'original_path' => $logoPath,
            'original_filename' => 'sony-logo.png',
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
            'id' => 120110,
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

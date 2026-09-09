<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralBrandOwnership;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Models\Organization;
use App\Support\Normalization\OrganizationNameNormalizer;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

final class BrandFormFixture
{
    public const VERSION = 'brand-form-v5';

    public const BRAND_ID = 3;

    public const OWNERSHIP_BRAND_ID = 13016;

    public const ALTERNATIVE_ORGANIZATION_ID = 1301602;

    public static function create(): CentralBrand
    {
        $timestamp = CarbonImmutable::parse('2026-08-13T10:00:00Z');

        $brand = CentralBrand::query()->findOrFail(self::BRAND_ID);
        $brand->forceFill([
            'name' => 'Apple',
            'slug' => 'apple',
            'status' => CentralBrandStatus::Active,
            'website_url' => 'https://www.apple.com/',
            'country_id' => CountryReference::id('US'),
            'founded_year' => 1976,
            'support_url' => 'https://support.apple.com/',
            'contact_email' => 'contact@apple.example',
            'primary_color' => '#000000',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveOrFail();

        self::createLegacyListRecord($timestamp);

        self::createOrganization(
            self::ALTERNATIVE_ORGANIZATION_ID,
            'Apple Operations International',
            $timestamp,
        );

        CentralBrand::factory()->create([
            'id' => self::OWNERSHIP_BRAND_ID,
            'name' => 'Zeta Ownership Journey Fixture',
            'slug' => 'zeta-ownership-journey-fixture',
            'status' => CentralBrandStatus::Draft,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return $brand;
    }

    private static function createLegacyListRecord(CarbonImmutable $timestamp): void
    {
        $legacyBrand = CentralBrand::factory()->create([
            'id' => 13013,
            'name' => 'Samsung Form Fixture',
            'slug' => 'samsung-form-fixture',
            'status' => CentralBrandStatus::Draft,
            'website_url' => 'https://www.samsung.com/',
            'country_id' => CountryReference::id('KR'),
            'founded_year' => 1938,
            'support_url' => 'https://www.samsung.com/support/',
            'contact_email' => 'support@example.com',
            'primary_color' => '#1428A0',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $logoBytes = (string) file_get_contents(base_path('tests/Fixtures/media/brand-logo-a.png')).'CA013';
        $logoPath = 'media/originals/ca-013-samsung-logo.png';
        Storage::disk('public')->put($logoPath, $logoBytes);

        $asset = new MediaAsset;
        $asset->forceFill([
            'id' => 1301301,
            'uuid' => '00000000-0000-4000-8000-000000013013',
            'type' => 'image',
            'source' => 'fixture',
            'disk' => 'public',
            'original_path' => $logoPath,
            'original_filename' => 'samsung-logo.png',
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
            'id' => 1301301,
            'media_asset_id' => $asset->getKey(),
            'entity_type' => MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND,
            'entity_id' => $legacyBrand->getKey(),
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

        $owner = self::createOrganization(
            1301601,
            'Samsung Electronics Co., Ltd. — Global Corporate Holdings',
            $timestamp,
        );
        $ownership = new CentralBrandOwnership;
        $ownership->forceFill([
            'id' => 1301601,
            'central_brand_id' => $legacyBrand->getKey(),
            'organization_id' => $owner->getKey(),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveOrFail();
    }

    private static function createOrganization(
        int $id,
        string $name,
        CarbonImmutable $timestamp,
    ): Organization {
        $normalizedName = OrganizationNameNormalizer::search($name);
        $organization = new Organization;
        $organization->forceFill([
            'id' => $id,
            'name' => $name,
            'normalized_name' => $normalizedName,
            'normalized_name_prefix' => OrganizationNameNormalizer::prefixForNormalizedName($normalizedName),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveOrFail();

        return $organization;
    }

    private function __construct() {}
}

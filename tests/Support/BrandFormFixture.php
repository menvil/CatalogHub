<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

final class BrandFormFixture
{
    public const VERSION = 'brand-form-v3';

    public const BRAND_ID = 13013;

    public static function create(): CentralBrand
    {
        $timestamp = CarbonImmutable::parse('2026-08-13T10:00:00Z');

        $brand = CentralBrand::factory()->create([
            'id' => self::BRAND_ID,
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

        $logoSource = base_path('tests/Fixtures/media/brand-logo-a.png');
        $logoPath = 'media/originals/ca-013-samsung-logo.png';
        // Keep the visual fixture distinct from CA-014 upload fixtures so Media
        // deduplication cannot make browser acceptance depend on test order.
        $logoBytes = (string) file_get_contents($logoSource).'CA013';
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

        return $brand;
    }

    private function __construct() {}
}

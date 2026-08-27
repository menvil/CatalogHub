<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\MediaAssignment;
use App\Services\Media\MediaVariantGenerator;
use App\Services\Media\MediaVariantProfile;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Storage;

final class BrandMediaFixture
{
    public const VERSION = 'brand-media-v2';

    public const BRAND_ID = 14014;

    public const ASSET_ID = 1401401;

    public static function create(): CentralBrand
    {
        $timestamp = CarbonImmutable::parse('2026-08-14T14:14:00Z');
        $brand = CentralBrand::factory()->create([
            'id' => self::BRAND_ID,
            'name' => 'Zyxel Identity Fixture',
            'slug' => 'zyxel-identity-fixture',
            'status' => CentralBrandStatus::Active,
            'website_url' => 'https://cataloghub.test',
            'country_id' => CountryReference::id('BG'),
            'founded_year' => 2024,
            'support_url' => 'https://cataloghub.test/support',
            'contact_email' => null,
            'primary_color' => '#1D4ED8',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        $bytes = self::fixtureLogoBytes();
        $path = 'media/originals/ca-014/cataloghub-primary-logo.png';
        Storage::disk('public')->put($path, $bytes);

        $asset = new MediaAsset;
        $asset->forceFill([
            'id' => self::ASSET_ID,
            'uuid' => '00000000-0000-4000-8000-000000014014',
            'type' => 'image',
            'source' => 'fixture',
            'disk' => 'public',
            'original_path' => $path,
            'original_filename' => 'cataloghub-primary-logo.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($bytes),
            'width' => 160,
            'height' => 96,
            'checksum' => 'sha256:'.hash('sha256', $bytes),
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveOrFail();

        app(MediaVariantGenerator::class)->generateForAsset((int) $asset->getKey(), MediaVariantProfile::BrandLogo);
        $asset->variants()->update(['created_at' => $timestamp, 'updated_at' => $timestamp]);

        $assignment = new MediaAssignment;
        $assignment->forceFill([
            'id' => self::ASSET_ID,
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

    private static function fixtureLogoBytes(): string
    {
        $source = (string) file_get_contents(base_path('tests/Fixtures/media/brand-logo-a.png'));
        $image = imagecreatefromstring($source);
        if ($image === false) {
            throw new \RuntimeException('Unable to decode the deterministic CA-014 fixture logo.');
        }

        imagesetpixel($image, 0, 0, imagecolorallocatealpha($image, 29, 78, 216, 0));
        ob_start();
        $written = imagepng($image, null, 6);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        if (! $written || $bytes === '') {
            throw new \RuntimeException('Unable to encode the deterministic CA-014 fixture logo.');
        }

        return $bytes;
    }
}

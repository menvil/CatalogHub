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
    public const VERSION = 'brand-media-v5';

    public const BRAND_ID = 14014;

    public const ASSET_ID = 1401401;

    public static function create(): CentralBrand
    {
        $timestamp = CarbonImmutable::parse('2026-08-14T14:14:00Z');
        $brand = CentralBrand::factory()->create([
            'id' => self::BRAND_ID,
            'name' => 'Zyxel Apple Fixture',
            'slug' => 'zyxel-apple-fixture',
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
        $path = 'media/originals/ca-014/apple-logo-black.png';
        Storage::disk('public')->put($path, $bytes);

        $asset = new MediaAsset;
        $asset->forceFill([
            'id' => self::ASSET_ID,
            'uuid' => '00000000-0000-4000-8000-000000014014',
            'type' => 'image',
            'source' => 'fixture',
            'disk' => 'public',
            'original_path' => $path,
            'original_filename' => 'apple-logo-black.png',
            'mime_type' => 'image/png',
            'file_size' => strlen($bytes),
            'width' => 640,
            'height' => 400,
            'checksum' => 'sha256:'.hash('sha256', $bytes),
            'status' => 'active',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveOrFail();

        self::createPickerAssets($timestamp, $bytes);

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
        $image = imagecreatetruecolor(640, 400);
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefill($image, 0, 0, $transparent);
        imagealphablending($image, true);
        $black = imagecolorallocate($image, 18, 24, 38);

        imagefilledellipse($image, 265, 235, 210, 235, $black);
        imagefilledellipse($image, 380, 235, 210, 235, $black);
        imagefilledellipse($image, 322, 285, 240, 185, $black);
        imagefilledpolygon($image, [320, 112, 354, 62, 390, 50, 376, 91, 342, 119], $black);
        imagealphablending($image, false);
        imagefilledellipse($image, 468, 165, 110, 94, $transparent);
        imagefilledellipse($image, 470, 236, 92, 74, $transparent);

        ob_start();
        $written = imagepng($image, null, 6);
        $bytes = (string) ob_get_clean();
        imagedestroy($image);
        if (! $written || $bytes === '') {
            throw new \RuntimeException('Unable to encode the deterministic CA-014 fixture logo.');
        }

        return $bytes;
    }

    private static function createPickerAssets(CarbonImmutable $timestamp, string $bytes): void
    {
        $filenames = [
            'apple-wordmark-black.png',
            'apple-retail-signage.png',
            'apple-product-mark.png',
            'apple-partner-lockup.png',
            'apple-legacy-logo.png',
        ];

        foreach ($filenames as $offset => $filename) {
            $id = 1401301 + $offset;
            $path = 'media/originals/ca-014/'.$filename;
            Storage::disk('public')->put($path, $bytes);

            $candidate = new MediaAsset;
            $candidate->forceFill([
                'id' => $id,
                'uuid' => sprintf('00000000-0000-4000-8000-%012d', $id),
                'type' => 'image',
                'source' => 'brand migration',
                'disk' => 'public',
                'original_path' => $path,
                'original_filename' => $filename,
                'mime_type' => 'image/png',
                'file_size' => strlen($bytes),
                'width' => 640,
                'height' => 400,
                'checksum' => 'sha256:'.hash('sha256', $bytes.$filename),
                'status' => 'active',
                'created_at' => $timestamp->subMinute(),
                'updated_at' => $timestamp->subMinute(),
            ])->saveOrFail();
        }
    }
}

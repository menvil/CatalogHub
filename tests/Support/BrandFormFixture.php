<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Carbon\CarbonImmutable;

final class BrandFormFixture
{
    public const VERSION = 'brand-form-v2';

    public const BRAND_ID = 13013;

    public static function create(): CentralBrand
    {
        $timestamp = CarbonImmutable::parse('2026-08-13T10:00:00Z');

        return CentralBrand::factory()->create([
            'id' => self::BRAND_ID,
            'name' => 'Samsung Form Fixture',
            'slug' => 'samsung-form-fixture',
            'status' => CentralBrandStatus::Draft,
            'website_url' => 'https://www.samsung.com',
            'country_id' => CountryReference::id('KR'),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);
    }

    private function __construct() {}
}

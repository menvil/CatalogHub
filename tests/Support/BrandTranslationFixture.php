<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use Carbon\CarbonImmutable;
use RuntimeException;

final class BrandTranslationFixture
{
    public const VERSION = 'brand-translations-v1';

    public static function create(): void
    {
        $brand = CentralBrand::query()->find(BrandDetailFixture::ACTIVE_BRAND_ID);

        if (! $brand instanceof CentralBrand || $brand->slug !== 'samsung') {
            throw new RuntimeException('BrandTranslationFixture requires the deterministic Samsung Brand detail fixture.');
        }

        Locale::query()->update(['is_default' => false]);

        foreach ([
            ['code' => 'en-US', 'language_code' => 'en', 'region_code' => 'US', 'name' => 'English', 'native_name' => 'English', 'is_default' => true, 'position' => 0],
            ['code' => 'de-DE', 'language_code' => 'de', 'region_code' => 'DE', 'name' => 'German', 'native_name' => 'Deutsch', 'is_default' => false, 'position' => 1],
            ['code' => 'fr-FR', 'language_code' => 'fr', 'region_code' => 'FR', 'name' => 'French', 'native_name' => 'Français', 'is_default' => false, 'position' => 2],
        ] as $attributes) {
            $locale = Locale::query()->firstOrNew(['code' => $attributes['code']]);
            $locale->forceFill([
                ...$attributes,
                'direction' => 'ltr',
                'is_active' => true,
            ])->saveOrFail();
        }

        BrandTranslation::query()
            ->whereIn('brand_id', [BrandDetailFixture::NEEDS_ATTENTION_BRAND_ID, BrandDetailFixture::COMPLETE_BRAND_ID])
            ->delete();

        foreach (Locale::query()->active()->orderBy('position')->orderBy('code')->get() as $locale) {
            BrandTranslation::factory()->create([
                'brand_id' => BrandDetailFixture::COMPLETE_BRAND_ID,
                'locale_id' => $locale->getKey(),
                'locale' => $locale->code,
                'name' => 'Sony '.$locale->code,
                'tagline' => 'Complete deterministic Brand translation.',
                'short_description' => 'Complete Brand quality fixture for '.$locale->code.'.',
                'description' => null,
                'seo_title' => null,
                'seo_description' => null,
                'status' => TranslationStatus::HumanReviewed,
            ]);
        }

        CentralBrand::query()->whereKey(BrandDetailFixture::NEEDS_ATTENTION_BRAND_ID)->update([
            'updated_at' => CarbonImmutable::parse('2026-07-26T09:00:00Z'),
        ]);
        CentralBrand::query()->whereKey(BrandDetailFixture::COMPLETE_BRAND_ID)->update([
            'updated_at' => CarbonImmutable::parse('2026-07-27T09:00:00Z'),
        ]);
    }

    private function __construct() {}
}

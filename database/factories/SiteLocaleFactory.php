<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Locale;
use App\Models\Site;
use App\Models\SiteLocale;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SiteLocale> */
final class SiteLocaleFactory extends Factory
{
    protected $model = SiteLocale::class;

    public function definition(): array
    {
        $locale = Locale::query()->firstOrCreate(
            ['code' => 'en-US'],
            [
                'language_code' => 'en',
                'region_code' => 'US',
                'name' => 'English (United States)',
                'native_name' => 'English (United States)',
                'direction' => 'ltr',
                'is_active' => true,
                'is_default' => false,
                'position' => 0,
            ],
        );

        return [
            'site_id' => Site::factory()->state(['default_locale' => 'en-US']),
            'locale_code' => $locale->code,
            'is_default' => true,
            'is_enabled' => true,
            'position' => 0,
        ];
    }
}

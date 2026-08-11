<?php

namespace Database\Factories;

use App\Enums\SiteDomainType;
use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Models\Locale;
use App\Models\Market;
use App\Models\Site;
use App\Models\SiteDomain;
use Illuminate\Database\Eloquent\Factories\Factory;
use InvalidArgumentException;

/** @extends Factory<Site> */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        $code = fake()->unique()->slug(2);

        return [
            'market_id' => Market::factory(),
            'code' => $code,
            'name' => fake()->company(),
            'domain' => $code.'.test',
            'mode' => SiteMode::MultiCategory,
            'default_locale' => 'en-US',
            'currency_code' => 'EUR',
            'timezone' => 'UTC',
            'status' => SiteStatus::default(),
            'settings_json' => [],
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Site $site): void {
            if (is_string($site->domain) && $site->domain !== '') {
                $site->domains()->firstOrCreate(
                    ['host' => SiteDomain::normalizeHost($site->domain)],
                    [
                        'type' => SiteDomainType::Primary,
                        'is_primary' => true,
                        'is_active' => true,
                    ],
                );
            }
        });
    }

    public function active(): static
    {
        return $this->state(fn (): array => ['status' => SiteStatus::Active]);
    }

    public function archived(): static
    {
        return $this->state(fn (): array => ['status' => SiteStatus::Archived]);
    }

    public function multi(): static
    {
        return $this->state(fn (): array => ['mode' => SiteMode::MultiCategory]);
    }

    public function single(): static
    {
        return $this->state(fn (): array => ['mode' => SiteMode::SingleCategory]);
    }

    /** @param list<string> $locales */
    public function withRuntimeContext(array $locales = ['en-US'], ?string $defaultLocale = null): static
    {
        if ($locales === []) {
            throw new InvalidArgumentException('At least one site locale is required.');
        }

        if (count($locales) !== count(array_unique($locales, SORT_STRING))) {
            throw new InvalidArgumentException('Site locale codes must be unique.');
        }

        $defaultLocale ??= $locales[0];

        if (! in_array($defaultLocale, $locales, true)) {
            throw new InvalidArgumentException('The default site locale must be present in the locale list.');
        }

        return $this
            ->state(fn (): array => ['default_locale' => $defaultLocale])
            ->afterCreating(function (Site $site) use ($locales, $defaultLocale): void {
                foreach ($locales as $position => $code) {
                    [$language, $region] = array_pad(explode('-', $code, 2), 2, null);
                    Locale::query()->firstOrCreate(
                        ['code' => $code],
                        [
                            'language_code' => strtolower($language),
                            'region_code' => $region,
                            'name' => $code,
                            'native_name' => $code,
                            'direction' => 'ltr',
                            'is_active' => true,
                            'is_default' => false,
                            'position' => $position,
                        ],
                    );
                    $site->locales()->create([
                        'locale_code' => $code,
                        'is_default' => $code === $defaultLocale,
                        'is_enabled' => true,
                        'position' => $position,
                    ]);
                }
            });
    }
}

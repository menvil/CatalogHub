<?php

namespace Database\Factories;

use App\Enums\SiteDomainType;
use App\Enums\SiteMembershipRole;
use App\Enums\UserRole;
use App\Models\Locale;
use App\Models\Site;
use App\Models\SiteDomain;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'role' => UserRole::default(),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function centralAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => UserRole::CentralAdmin,
        ]);
    }

    public function siteAdmin(Site $site): static
    {
        $runtimeConfiguration = $this->validateSiteRuntimeConfiguration($site);

        return $this
            ->state(fn (): array => [
                'site_id' => $site->getKey(),
                'role' => UserRole::SiteAdmin,
            ])
            ->afterCreating(function (User $user) use ($site, $runtimeConfiguration): void {
                $this->writeSiteRuntimeConfiguration($site, $runtimeConfiguration);
                $user->memberships()->create([
                    'site_id' => $site->getKey(),
                    'role' => SiteMembershipRole::SiteAdmin,
                    'is_active' => true,
                ]);
            });
    }

    /** @return array{host: ?string, locale: ?string} */
    private function validateSiteRuntimeConfiguration(Site $site): array
    {
        $host = null;

        if (! $site->domains()->where('is_primary', true)->where('is_active', true)->exists()) {
            if (! is_string($site->domain) || trim($site->domain) === '') {
                throw new InvalidArgumentException('An active primary site domain is required.');
            }

            $host = SiteDomain::normalizeHost($site->domain);
        }

        $locale = null;

        if (! $site->locales()->where('is_default', true)->exists()) {
            $locale = (string) $site->default_locale;

            if (preg_match('/^[a-z]{2,3}(?:-[A-Z]{2})?$/', $locale) !== 1) {
                throw new InvalidArgumentException('A valid default site locale is required.');
            }
        }

        return ['host' => $host, 'locale' => $locale];
    }

    /** @param array{host: ?string, locale: ?string} $configuration */
    private function writeSiteRuntimeConfiguration(Site $site, array $configuration): void
    {
        if ($configuration['host'] !== null) {
            $site->domains()->create([
                'host' => $configuration['host'],
                'type' => SiteDomainType::Primary,
                'is_primary' => true,
                'is_active' => true,
            ]);
        }

        $code = $configuration['locale'];

        if ($code === null) {
            return;
        }

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
                'position' => 0,
            ],
        );
        $site->locales()->create([
            'locale_code' => $code,
            'is_default' => true,
            'is_enabled' => true,
            'position' => 0,
        ]);
    }
}

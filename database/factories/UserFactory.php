<?php

namespace Database\Factories;

use App\Enums\SiteDomainType;
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
        return $this
            ->state(fn (): array => [
                'site_id' => $site->getKey(),
                'role' => UserRole::SiteAdmin,
            ])
            ->afterCreating(function () use ($site): void {
                $this->ensureSiteRuntimeConfiguration($site);
            });
    }

    private function ensureSiteRuntimeConfiguration(Site $site): void
    {
        if (! $site->domains()->where('is_primary', true)->where('is_active', true)->exists()) {
            if (! is_string($site->domain) || trim($site->domain) === '') {
                throw new InvalidArgumentException('An active primary site domain is required.');
            }

            $site->domains()->create([
                'host' => SiteDomain::normalizeHost($site->domain),
                'type' => SiteDomainType::Primary,
                'is_primary' => true,
                'is_active' => true,
            ]);
        }

        if ($site->locales()->where('is_default', true)->exists()) {
            return;
        }

        $code = (string) $site->default_locale;

        if (preg_match('/^[a-z]{2,3}(?:-[A-Z]{2})?$/', $code) !== 1) {
            throw new InvalidArgumentException('A valid default site locale is required.');
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

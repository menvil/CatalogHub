<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\SiteDomainType;
use App\Models\Site;
use App\Models\SiteDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SiteDomain> */
final class SiteDomainFactory extends Factory
{
    protected $model = SiteDomain::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'host' => fake()->unique()->domainName(),
            'type' => SiteDomainType::Alias,
            'is_primary' => false,
            'is_active' => true,
        ];
    }

    public function primary(): static
    {
        return $this->state(fn (): array => [
            'type' => SiteDomainType::Primary,
            'is_primary' => true,
        ]);
    }

    public function preview(): static
    {
        return $this->state(fn (): array => [
            'type' => SiteDomainType::Preview,
            'is_primary' => false,
        ]);
    }
}

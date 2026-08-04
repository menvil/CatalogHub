<?php

namespace Database\Factories;

use App\Enums\SiteMembershipRole;
use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SiteMembership> */
final class SiteMembershipFactory extends Factory
{
    protected $model = SiteMembership::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'site_id' => Site::factory(),
            'role' => SiteMembershipRole::SiteAdmin,
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}

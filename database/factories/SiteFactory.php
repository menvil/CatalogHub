<?php

namespace Database\Factories;

use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Models\Market;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Site> */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        $code = fake()->unique()->slug(2);

        return ['market_id' => Market::factory(), 'code' => $code, 'name' => fake()->company(), 'domain' => $code.'.test', 'mode' => SiteMode::MultiCategory, 'default_locale' => 'en-US', 'status' => SiteStatus::default(), 'settings_json' => []];
    }
}

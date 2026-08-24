<?php

namespace Database\Factories;

use App\Enums\TranslationStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Locale;
use App\Models\Translations\BrandTranslation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<BrandTranslation> */
final class BrandTranslationFactory extends Factory
{
    protected $model = BrandTranslation::class;

    public function definition(): array
    {
        return [
            'brand_id' => CentralBrand::factory(),
            'locale_id' => Locale::factory(),
            'locale' => fn (array $attributes): ?string => Locale::query()->find($attributes['locale_id'])?->code,
            'name' => fake()->company(),
            'tagline' => fake()->optional()->sentence(4),
            'short_description' => fake()->optional()->sentence(),
            'description' => fake()->optional()->paragraph(),
            'seo_title' => fake()->optional()->sentence(3),
            'seo_description' => fake()->optional()->sentence(),
            'status' => TranslationStatus::HumanReviewed,
            'source_hash' => null,
        ];
    }

    public function machineTranslated(): static
    {
        return $this->state(fn (): array => ['status' => TranslationStatus::MachineTranslated]);
    }

    public function humanReviewed(): static
    {
        return $this->state(fn (): array => ['status' => TranslationStatus::HumanReviewed]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => TranslationStatus::Approved,
            'approved_at' => now(),
            'approved_by_user_id' => User::factory(),
        ]);
    }

    public function outdated(): static
    {
        return $this->state(fn (): array => ['status' => TranslationStatus::Outdated]);
    }
}

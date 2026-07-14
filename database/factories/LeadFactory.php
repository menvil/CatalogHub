<?php

namespace Database\Factories;

use App\Enums\LeadStatus;
use App\Enums\LeadType;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Lead;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Lead> */
class LeadFactory extends Factory
{
    protected $model = Lead::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'central_product_id' => CentralProduct::factory(),
            'central_category_id' => CentralCategory::factory(),
            'type' => LeadType::BuyingAdvice,
            'status' => LeadStatus::New,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'phone' => null,
            'city' => fake()->city(),
            'message' => fake()->sentence(),
            'locale' => 'en-US',
            'source' => 'product_page',
            'consent_accepted_at' => now(),
            'metadata' => null,
        ];
    }

    public function withoutProduct(): static
    {
        return $this->state(fn (): array => ['central_product_id' => null]);
    }

    public function withoutCategory(): static
    {
        return $this->state(fn (): array => ['central_category_id' => null]);
    }

    public function spam(): static
    {
        return $this->state(fn (): array => ['status' => LeadStatus::Spam]);
    }
}

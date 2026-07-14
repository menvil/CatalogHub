<?php

namespace Database\Factories;

use App\Enums\ReviewStatus;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Review;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Review> */
class ReviewFactory extends Factory
{
    protected $model = Review::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'central_product_id' => CentralProduct::factory(),
            'author_name' => fake()->name(),
            'author_email' => fake()->safeEmail(),
            'rating' => fake()->numberBetween(1, 5),
            'pros' => fake()->sentence(),
            'cons' => null,
            'comment' => fake()->paragraph(),
            'status' => ReviewStatus::Pending,
            'locale' => 'en-US',
            'approved_at' => null,
            'rejected_at' => null,
            'spam_marked_at' => null,
            'metadata' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'status' => ReviewStatus::Pending,
            'approved_at' => null,
            'rejected_at' => null,
            'spam_marked_at' => null,
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (): array => [
            'status' => ReviewStatus::Approved,
            'approved_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn (): array => [
            'status' => ReviewStatus::Rejected,
            'rejected_at' => now(),
        ]);
    }

    public function spam(): static
    {
        return $this->state(fn (): array => [
            'status' => ReviewStatus::Spam,
            'spam_marked_at' => now(),
        ]);
    }
}

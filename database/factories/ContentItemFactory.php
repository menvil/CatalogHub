<?php

namespace Database\Factories;

use App\Enums\ContentType;
use App\Models\ContentItem;
use App\Models\Site;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ContentItem> */
class ContentItemFactory extends Factory
{
    protected $model = ContentItem::class;

    public function definition(): array
    {
        return [
            'site_id' => Site::factory(),
            'type' => ContentType::Article,
            'status' => 'draft',
            'published_at' => null,
            'archived_at' => null,
            'created_by_user_id' => null,
            'updated_by_user_id' => null,
            'metadata' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => 'published',
            'published_at' => now(),
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\ContentItem;
use App\Models\ContentTranslation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ContentTranslation> */
class ContentTranslationFactory extends Factory
{
    protected $model = ContentTranslation::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(4);

        return [
            'content_item_id' => ContentItem::factory(),
            'locale' => 'en-US',
            'slug' => Str::slug($title),
            'title' => $title,
            'excerpt' => fake()->sentence(),
            'body' => fake()->paragraphs(3, true),
            'body_json' => null,
            'status' => 'draft',
            'meta_title' => null,
            'meta_description' => null,
            'og_title' => null,
            'og_description' => null,
            'source_hash' => null,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => ['status' => 'published']);
    }
}

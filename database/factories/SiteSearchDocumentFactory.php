<?php

namespace Database\Factories;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Models\Site;
use App\Models\SiteSearchDocument;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SiteSearchDocument> */
class SiteSearchDocumentFactory extends Factory
{
    protected $model = SiteSearchDocument::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);

        return [
            'site_id' => Site::factory(),
            'locale' => 'en-US',
            'document_type' => 'product',
            'document_id' => fake()->unique()->numberBetween(1, 1_000_000),
            'title' => str($title)->headline()->toString(),
            'slug' => str($title)->slug()->toString(),
            'status' => ProjectionStatus::Active,
            'search_text' => $title,
            'min_price' => null,
            'max_price' => null,
            'offers_count' => 0,
            'in_stock' => false,
            'filter_values_json' => [],
            'sort_values_json' => [],
            'payload_json' => [],
            'checksum' => fake()->sha256(),
            'built_at' => now(),
            'stale_at' => null,
        ];
    }
}

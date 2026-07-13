<?php

namespace Database\Factories;

use App\Models\Imports\NormalizedProductDraft;
use App\Models\Imports\RawProduct;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<NormalizedProductDraft>
 */
class NormalizedProductDraftFactory extends Factory
{
    protected $model = NormalizedProductDraft::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'raw_product_id' => RawProduct::factory(),
            'import_batch_id' => fn (array $attributes): int => RawProduct::query()
                ->findOrFail($attributes['raw_product_id'])
                ->import_batch_id,
            'matched_central_product_id' => null,
            'brand_id' => null,
            'category_id' => null,
            'title' => $title,
            'slug' => Str::slug($title),
            'normalized_payload_json' => ['title' => $title],
            'attributes_json' => [],
            'media_json' => [],
            'confidence' => '0.0000',
            'status' => 'pending_review',
            'review_notes' => null,
            'approved_by_user_id' => null,
            'approved_at' => null,
        ];
    }
}

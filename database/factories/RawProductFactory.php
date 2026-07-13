<?php

namespace Database\Factories;

use App\Models\Imports\ImportBatch;
use App\Models\Imports\RawProduct;
use App\Services\Imports\RawPayloadHasher;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<RawProduct>
 */
class RawProductFactory extends Factory
{
    protected $model = RawProduct::class;

    public function definition(): array
    {
        $payload = [
            'id' => (string) Str::uuid(),
            'title' => fake()->sentence(3),
            'brand' => fake()->company(),
            'category' => fake()->word(),
        ];

        return [
            'import_batch_id' => ImportBatch::factory(),
            'import_source_id' => fn (array $attributes): int => ImportBatch::query()
                ->findOrFail($attributes['import_batch_id'])
                ->import_source_id,
            'external_id' => $payload['id'],
            'source_row_number' => fake()->numberBetween(1, 1000),
            'raw_title' => $payload['title'],
            'raw_brand' => $payload['brand'],
            'raw_category' => $payload['category'],
            'raw_payload_json' => $payload,
            'payload_hash' => (new RawPayloadHasher)->hash($payload),
            'status' => 'pending',
            'error_message' => null,
        ];
    }
}

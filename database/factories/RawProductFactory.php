<?php

namespace Database\Factories;

use App\Models\Imports\ImportBatch;
use App\Models\Imports\ImportSource;
use App\Models\Imports\RawProduct;
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
            'import_source_id' => ImportSource::factory(),
            'external_id' => $payload['id'],
            'source_row_number' => fake()->numberBetween(1, 1000),
            'raw_title' => $payload['title'],
            'raw_brand' => $payload['brand'],
            'raw_category' => $payload['category'],
            'raw_payload_json' => $payload,
            'payload_hash' => hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR)),
            'status' => 'pending',
            'error_message' => null,
        ];
    }
}

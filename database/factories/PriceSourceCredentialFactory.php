<?php

namespace Database\Factories;

use App\Enums\PriceSourceCredentialStatus;
use App\Models\PriceSource;
use App\Models\PriceSourceCredential;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<PriceSourceCredential> */
class PriceSourceCredentialFactory extends Factory
{
    protected $model = PriceSourceCredential::class;

    public function definition(): array
    {
        return [
            'price_source_id' => PriceSource::factory(),
            'encrypted_credentials_json' => fake()->sha256(),
            'status' => PriceSourceCredentialStatus::Missing,
            'last_rotated_at' => null,
        ];
    }
}

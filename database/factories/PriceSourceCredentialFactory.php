<?php

namespace Database\Factories;

use App\Enums\PriceSourceCredentialStatus;
use App\Models\PriceSource;
use App\Models\PriceSourceCredential;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/** @extends Factory<PriceSourceCredential> */
class PriceSourceCredentialFactory extends Factory
{
    protected $model = PriceSourceCredential::class;

    public function definition(): array
    {
        return [
            'price_source_id' => PriceSource::factory(),
            'encrypted_credentials_json' => Crypt::encryptString(json_encode([
                'api_key' => 'factory-api-key',
            ], JSON_THROW_ON_ERROR)),
            'status' => PriceSourceCredentialStatus::Missing,
            'last_rotated_at' => null,
        ];
    }
}

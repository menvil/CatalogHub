<?php

namespace Database\Factories;

use App\Enums\RawPriceOfferStatus;
use App\Models\ExternalProductMapping;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\RawPriceOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RawPriceOffer> */
class RawPriceOfferFactory extends Factory
{
    protected $model = RawPriceOffer::class;

    public function definition(): array
    {
        return [
            'price_source_id' => PriceSource::factory(),
            'price_source_sync_log_id' => fn (array $attributes): int => PriceSourceSyncLog::factory()->create([
                'price_source_id' => $attributes['price_source_id'],
            ])->id,
            'external_product_id' => fake()->unique()->uuid(),
            'external_sku' => strtoupper(fake()->bothify('SKU-####-??')),
            'external_title' => fake()->words(4, true),
            'raw_payload_json' => ['price' => 99.99, 'currency' => 'EUR'],
            'normalized_payload_json' => null,
            'status' => RawPriceOfferStatus::Fetched,
            'error_message' => null,
            'fetched_at' => now(),
        ];
    }

    public function fetched(): static
    {
        return $this->state(fn (): array => [
            'normalized_payload_json' => null,
            'status' => RawPriceOfferStatus::Fetched,
            'error_message' => null,
        ]);
    }

    public function normalized(): static
    {
        return $this->state(fn (array $attributes): array => [
            'normalized_payload_json' => [
                'external_product_id' => $attributes['external_product_id'] ?? null,
                'external_sku' => $attributes['external_sku'] ?? null,
                'external_title' => $attributes['external_title'] ?? null,
                'merchant_name' => 'Example Shop',
                'price' => '99.99',
                'currency' => 'EUR',
                'availability' => 'in_stock',
                'condition' => 'new',
                'fetched_at' => now()->toISOString(),
            ],
            'status' => RawPriceOfferStatus::Normalized,
            'error_message' => null,
        ]);
    }

    public function matched(): static
    {
        return $this->normalized()->state(fn (): array => ['status' => RawPriceOfferStatus::Matched]);
    }

    public function forMapping(ExternalProductMapping $mapping): static
    {
        return $this->state(fn (): array => [
            'price_source_id' => $mapping->price_source_id,
            'external_product_id' => $mapping->external_product_id,
            'external_sku' => $mapping->external_sku,
            'external_title' => $mapping->external_title,
        ]);
    }
}

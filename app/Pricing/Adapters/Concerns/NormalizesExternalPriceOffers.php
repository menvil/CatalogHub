<?php

namespace App\Pricing\Adapters\Concerns;

use App\Data\Pricing\ExternalPriceOfferData;
use App\Enums\OfferAvailability;
use App\Enums\OfferCondition;
use App\Models\PriceSource;
use Carbon\CarbonImmutable;
use InvalidArgumentException;

trait NormalizesExternalPriceOffers
{
    /** @param array<string, mixed> $payload */
    private function normalizedOffer(PriceSource $source, array $payload): ExternalPriceOfferData
    {
        $currency = $this->currency($source, $payload);

        return new ExternalPriceOfferData(
            externalProductId: $this->stringOrNull($payload['external_product_id'] ?? null),
            externalSku: $this->stringOrNull($payload['external_sku'] ?? $payload['sku'] ?? null),
            externalTitle: $this->stringOrNull($payload['external_title'] ?? $payload['title'] ?? null),
            brandName: $this->stringOrNull($payload['brand_name'] ?? $payload['brand'] ?? null),
            modelName: $this->stringOrNull($payload['model_name'] ?? $payload['model'] ?? null),
            merchantName: $this->stringOrNull($payload['merchant_name'] ?? $payload['merchant'] ?? null)
                ?? $source->name,
            price: $this->money($payload['price'] ?? null, 'price'),
            currency: $currency,
            availability: $this->availability($payload['availability'] ?? null),
            condition: $this->condition($payload['condition'] ?? null),
            url: $this->stringOrNull($payload['url'] ?? null),
            payload: $payload,
            fetchedAt: isset($payload['fetched_at'])
                ? CarbonImmutable::parse((string) $payload['fetched_at'])
                : CarbonImmutable::now(),
            deliveryPrice: array_key_exists('delivery_price', $payload)
                && $payload['delivery_price'] !== null
                && $payload['delivery_price'] !== ''
                    ? $this->money($payload['delivery_price'], 'delivery_price')
                    : null,
            deliveryTime: $this->stringOrNull($payload['delivery_time'] ?? null),
        );
    }

    /** @param array<string, mixed> $payload */
    private function currency(PriceSource $source, array $payload): string
    {
        $currency = $this->stringOrNull($payload['currency'] ?? null)
            ?? $this->stringOrNull($source->config_json['default_currency'] ?? null);

        if ($currency === null && ($source->config_json['allow_market_default_currency'] ?? false) === true) {
            $currency = $source->market->currency_code;
        }

        $currency = strtoupper((string) $currency);

        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException('A three-letter offer currency is required.');
        }

        return $currency;
    }

    private function money(mixed $value, string $field): string
    {
        if (! is_numeric($value) || (float) $value < 0) {
            throw new InvalidArgumentException("The offer {$field} must be a non-negative number.");
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function availability(mixed $value): OfferAvailability
    {
        $value = str((string) $value)->lower()->trim()->replace(['-', ' '], '_')->toString();

        return match ($value) {
            'in_stock', 'available', 'ships_tomorrow' => OfferAvailability::InStock,
            'out_of_stock', 'sold_out', 'unavailable' => OfferAvailability::OutOfStock,
            'preorder', 'pre_order' => OfferAvailability::Preorder,
            'backorder', 'back_order' => OfferAvailability::Backorder,
            default => OfferAvailability::Unknown,
        };
    }

    private function condition(mixed $value): OfferCondition
    {
        $value = str((string) $value)->lower()->trim()->toString();

        return match ($value) {
            'new' => OfferCondition::New,
            'used' => OfferCondition::Used,
            'refurbished', 'renewed' => OfferCondition::Refurbished,
            default => OfferCondition::Unknown,
        };
    }

    private function stringOrNull(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}

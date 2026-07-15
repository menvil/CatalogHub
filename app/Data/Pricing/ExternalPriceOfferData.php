<?php

namespace App\Data\Pricing;

use App\Enums\OfferAvailability;
use App\Enums\OfferCondition;
use Carbon\CarbonImmutable;

final readonly class ExternalPriceOfferData
{
    /** @param array<string, mixed> $payload */
    public function __construct(
        public ?string $externalProductId,
        public ?string $externalSku,
        public ?string $externalTitle,
        public ?string $brandName,
        public ?string $modelName,
        public string $merchantName,
        public string $price,
        public string $currency,
        public OfferAvailability $availability,
        public OfferCondition $condition,
        public ?string $url,
        public array $payload,
        public CarbonImmutable $fetchedAt,
        public ?string $deliveryPrice = null,
        public ?string $deliveryTime = null,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'external_product_id' => $this->externalProductId,
            'external_sku' => $this->externalSku,
            'external_title' => $this->externalTitle,
            'brand_name' => $this->brandName,
            'model_name' => $this->modelName,
            'merchant_name' => $this->merchantName,
            'price' => $this->price,
            'currency' => $this->currency,
            'availability' => $this->availability->value,
            'condition' => $this->condition->value,
            'delivery_price' => $this->deliveryPrice,
            'delivery_time' => $this->deliveryTime,
            'url' => $this->url,
            'payload' => $this->payload,
            'fetched_at' => $this->fetchedAt->toISOString(),
        ];
    }
}

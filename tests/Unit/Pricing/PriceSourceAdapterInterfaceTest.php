<?php

namespace Tests\Unit\Pricing;

use App\Contracts\Pricing\PriceSourceAdapterInterface;
use App\Data\Pricing\ExternalPriceOfferData;
use App\Data\Pricing\PriceSourceFetchResult;
use App\Enums\OfferAvailability;
use App\Enums\OfferCondition;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class PriceSourceAdapterInterfaceTest extends TestCase
{
    public function test_defines_price_source_adapter_contract(): void
    {
        $reflection = new ReflectionClass(PriceSourceAdapterInterface::class);

        $this->assertTrue($reflection->isInterface());
        $this->assertSame(
            ['supports', 'fetchOffers', 'normalizeOffer'],
            array_map(fn (ReflectionMethod $method): string => $method->getName(), $reflection->getMethods()),
        );
        $this->assertSame(PriceSourceFetchResult::class, (string) (new ReflectionMethod(
            PriceSourceAdapterInterface::class,
            'fetchOffers',
        ))->getReturnType());
    }

    public function test_external_offer_dto_serializes_normalized_values(): void
    {
        $fetchedAt = CarbonImmutable::parse('2026-07-15T12:00:00Z');
        $offer = new ExternalPriceOfferData(
            externalProductId: 'external-1',
            externalSku: 'SKU-1',
            externalTitle: 'Example product',
            brandName: 'Example',
            modelName: 'One',
            merchantName: 'Example Shop',
            price: '49.90',
            currency: 'EUR',
            availability: OfferAvailability::InStock,
            condition: OfferCondition::New,
            url: 'https://example.test/offer',
            payload: ['sku' => 'SKU-1'],
            fetchedAt: $fetchedAt,
        );

        $this->assertSame('in_stock', $offer->toArray()['availability']);
        $this->assertSame('new', $offer->toArray()['condition']);
        $this->assertSame($fetchedAt->toISOString(), $offer->toArray()['fetched_at']);
        $this->assertCount(1, PriceSourceFetchResult::fromOffers([['sku' => 'SKU-1']])->offers);
    }
}

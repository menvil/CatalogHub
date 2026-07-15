<?php

namespace Tests\Unit\Pricing\Adapters;

use App\Contracts\Pricing\PriceSourceAdapterInterface;
use App\Enums\OfferAvailability;
use App\Enums\PriceSourceType;
use App\Models\PriceSource;
use App\Pricing\Adapters\ManualOfferAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ManualOfferAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_implements_contract_and_fetches_manual_config_offers(): void
    {
        $source = PriceSource::factory()->manual()->create([
            'config_json' => ['offers' => [['sku' => 'K2', 'price' => 79.99, 'currency' => 'EUR']]],
        ]);
        $adapter = app(ManualOfferAdapter::class);

        $this->assertInstanceOf(PriceSourceAdapterInterface::class, $adapter);
        $this->assertTrue($adapter->supports($source));
        $this->assertCount(1, $adapter->fetchOffers($source)->offers);
    }

    public function test_normalizes_manual_offer_and_availability(): void
    {
        $source = PriceSource::factory()->manual()->create();
        $offer = app(ManualOfferAdapter::class)->normalizeOffer($source, [
            'external_product_id' => 'product-1',
            'sku' => 'K2',
            'title' => 'Keychron K2',
            'merchant_name' => 'Keyboard Shop',
            'price' => '79.99',
            'currency' => 'eur',
            'availability' => 'in stock',
        ]);

        $this->assertSame('K2', $offer->externalSku);
        $this->assertSame('79.99', $offer->price);
        $this->assertSame('EUR', $offer->currency);
        $this->assertSame(OfferAvailability::InStock, $offer->availability);
    }

    public function test_handles_empty_config_and_rejects_missing_currency(): void
    {
        $source = PriceSource::factory()->manual()->create(['config_json' => []]);
        $adapter = app(ManualOfferAdapter::class);

        $this->assertSame([], $adapter->fetchOffers($source)->offers);

        $this->expectException(InvalidArgumentException::class);
        $adapter->normalizeOffer($source, ['price' => 10]);
    }

    public function test_does_not_support_non_manual_sources(): void
    {
        $source = PriceSource::factory()->create(['type' => PriceSourceType::Api]);

        $this->assertFalse(app(ManualOfferAdapter::class)->supports($source));
    }
}

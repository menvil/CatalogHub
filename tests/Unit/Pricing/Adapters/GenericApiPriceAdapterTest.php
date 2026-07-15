<?php

namespace Tests\Unit\Pricing\Adapters;

use App\Contracts\Pricing\PriceSourceAdapterInterface;
use App\Enums\OfferAvailability;
use App\Enums\PriceSourceType;
use App\Models\PriceSource;
use App\Pricing\Adapters\GenericApiPriceAdapter;
use App\Services\Pricing\PriceSourceCredentialService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class GenericApiPriceAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetches_json_array_with_encrypted_credential_header(): void
    {
        Http::fake([
            'https://prices.example.test/offers' => Http::response([
                ['sku' => 'K2', 'title' => 'Keychron K2', 'price' => 79.99, 'currency' => 'EUR'],
            ]),
        ]);
        $source = PriceSource::factory()->create([
            'type' => PriceSourceType::Api,
            'config_json' => [
                'endpoint_url' => 'https://prices.example.test/offers',
                'headers' => ['Authorization' => 'credential:api_key'],
            ],
        ]);
        app(PriceSourceCredentialService::class)->store($source, ['api_key' => 'Bearer secret-token']);
        $adapter = app(GenericApiPriceAdapter::class);

        $result = $adapter->fetchOffers($source);

        $this->assertInstanceOf(PriceSourceAdapterInterface::class, $adapter);
        $this->assertTrue($adapter->supports($source));
        $this->assertCount(1, $result->offers);
        $this->assertStringNotContainsString('secret-token', json_encode($result->metadata, JSON_THROW_ON_ERROR));
        Http::assertSent(fn (Request $request): bool => $request->hasHeader('Authorization', 'Bearer secret-token'));
    }

    public function test_maps_nested_json_fields_to_normalized_offer(): void
    {
        $source = PriceSource::factory()->create([
            'type' => PriceSourceType::Api,
            'config_json' => [
                'endpoint_url' => 'https://prices.example.test/offers',
                'field_mapping' => [
                    'external_sku' => 'product.code',
                    'external_title' => 'product.name',
                    'merchant_name' => 'seller.name',
                    'price' => 'pricing.amount',
                    'currency' => 'pricing.currency',
                    'availability' => 'stock.status',
                ],
            ],
        ]);

        $offer = app(GenericApiPriceAdapter::class)->normalizeOffer($source, [
            'product' => ['code' => 'K2', 'name' => 'Keychron K2'],
            'seller' => ['name' => 'Keyboard Shop'],
            'pricing' => ['amount' => 79.99, 'currency' => 'EUR'],
            'stock' => ['status' => 'in stock'],
        ]);

        $this->assertSame('K2', $offer->externalSku);
        $this->assertSame('79.99', $offer->price);
        $this->assertSame(OfferAvailability::InStock, $offer->availability);
    }

    public function test_rejects_invalid_or_non_get_api_config(): void
    {
        $source = PriceSource::factory()->create([
            'type' => PriceSourceType::Api,
            'config_json' => ['endpoint_url' => 'https://prices.example.test/offers', 'method' => 'POST'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(GenericApiPriceAdapter::class)->fetchOffers($source);
    }
}

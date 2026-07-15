<?php

namespace Tests\Unit\Pricing\Adapters;

use App\Contracts\Pricing\PriceSourceAdapterInterface;
use App\Enums\OfferAvailability;
use App\Enums\PriceSourceType;
use App\Models\PriceSource;
use App\Pricing\Adapters\CsvFeedPriceAdapter;
use App\Services\Pricing\OutboundPriceSourceUrlGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class CsvFeedPriceAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalizes_configured_csv_columns(): void
    {
        $source = PriceSource::factory()->create([
            'type' => PriceSourceType::CsvFeed,
            'config_json' => [
                'sku_column' => 'product_code',
                'title_column' => 'product_name',
                'price_column' => 'amount',
                'currency_column' => 'iso_currency',
                'availability_column' => 'stock_state',
            ],
        ]);
        $adapter = app(CsvFeedPriceAdapter::class);
        $offer = $adapter->normalizeOffer($source, [
            'product_code' => '27GP850-B',
            'product_name' => 'LG UltraGear 27GP850-B',
            'amount' => '249.99',
            'iso_currency' => 'EUR',
            'stock_state' => 'available',
        ]);

        $this->assertInstanceOf(PriceSourceAdapterInterface::class, $adapter);
        $this->assertTrue($adapter->supports($source));
        $this->assertSame('27GP850-B', $offer->externalSku);
        $this->assertSame('249.99', $offer->price);
        $this->assertSame(OfferAvailability::InStock, $offer->availability);
    }

    public function test_parses_csv_content_and_handles_empty_feed(): void
    {
        $adapter = app(CsvFeedPriceAdapter::class);
        $source = PriceSource::factory()->create([
            'type' => PriceSourceType::CsvFeed,
            'config_json' => [
                'csv_content' => "sku,title,price,currency\nK2,\"Keychron\nK2\",79.99,EUR\n",
            ],
        ]);

        $offers = $adapter->fetchOffers($source)->offers;

        $this->assertCount(1, $offers);
        $this->assertSame("Keychron\nK2", $offers[0]['title']);

        $source->config_json = [];
        $this->assertSame([], $adapter->fetchOffers($source)->offers);
    }

    public function test_reports_invalid_csv_row_clearly(): void
    {
        $source = PriceSource::factory()->create([
            'type' => PriceSourceType::CsvFeed,
            'config_json' => ['price_column' => 'amount'],
        ]);

        $this->expectException(InvalidArgumentException::class);
        app(CsvFeedPriceAdapter::class)->normalizeOffer($source, ['currency' => 'EUR']);
    }

    public function test_fetches_only_allowlisted_public_feed_hosts(): void
    {
        Http::fake([
            'https://feeds.example.test/prices.csv' => Http::response(
                "sku,title,price,currency\nK2,Keychron K2,79.99,EUR\n",
            ),
        ]);
        $source = PriceSource::factory()->create([
            'type' => PriceSourceType::CsvFeed,
            'config_json' => [
                'feed_url' => 'https://feeds.example.test/prices.csv',
                'allowed_hosts' => ['feeds.example.test'],
            ],
        ]);
        $adapter = new CsvFeedPriceAdapter(new OutboundPriceSourceUrlGuard(
            static fn (string $host): array => ['93.184.216.34'],
        ));

        $this->assertCount(1, $adapter->fetchOffers($source)->offers);

        $source->config_json = [
            'feed_url' => 'https://feeds.example.test/prices.csv',
            'allowed_hosts' => ['other.example.test'],
        ];

        $this->expectException(InvalidArgumentException::class);
        $adapter->fetchOffers($source);
    }

    public function test_rejects_csv_feed_redirects(): void
    {
        Http::fake([
            'https://feeds.example.test/prices.csv' => Http::response('', 302, [
                'Location' => 'http://127.0.0.1/internal.csv',
            ]),
        ]);
        $source = PriceSource::factory()->create([
            'type' => PriceSourceType::CsvFeed,
            'config_json' => [
                'feed_url' => 'https://feeds.example.test/prices.csv',
                'allowed_hosts' => ['feeds.example.test'],
            ],
        ]);
        $adapter = new CsvFeedPriceAdapter(new OutboundPriceSourceUrlGuard(
            static fn (string $host): array => ['93.184.216.34'],
        ));

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('redirects');

        $adapter->fetchOffers($source);
    }
}

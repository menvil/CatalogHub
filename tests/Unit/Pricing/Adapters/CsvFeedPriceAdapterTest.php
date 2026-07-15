<?php

namespace Tests\Unit\Pricing\Adapters;

use App\Contracts\Pricing\PriceSourceAdapterInterface;
use App\Enums\OfferAvailability;
use App\Enums\PriceSourceType;
use App\Models\PriceSource;
use App\Pricing\Adapters\CsvFeedPriceAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                'csv_content' => "sku,title,price,currency\nK2,Keychron K2,79.99,EUR\n",
            ],
        ]);

        $this->assertCount(1, $adapter->fetchOffers($source)->offers);

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
}

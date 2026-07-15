<?php

namespace Tests\Feature\Jobs\Pricing;

use App\Enums\ExternalProductMappingStatus;
use App\Jobs\Pricing\StorePriceHistoryJob;
use App\Jobs\Pricing\UpdateMarketOffersJob;
use App\Models\ExternalProductMapping;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\RawPriceOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class UpdateMarketOffersJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_current_offer_for_approved_mapping_and_dispatches_history(): void
    {
        Bus::fake();
        $source = PriceSource::factory()->manual()->create();
        $mapping = ExternalProductMapping::factory()->approved()->for($source)->create([
            'external_product_id' => 'external-k2',
            'external_sku' => 'K2',
        ]);
        $log = PriceSourceSyncLog::factory()->running()->for($source)->create();
        RawPriceOffer::factory()->matched()->for($source)->create([
            'price_source_sync_log_id' => $log->id,
            'external_product_id' => 'external-k2',
            'external_sku' => 'K2',
            'normalized_payload_json' => [
                'external_product_id' => 'external-k2',
                'external_sku' => 'K2',
                'merchant_name' => 'Keyboard Shop',
                'price' => '79.99',
                'currency' => 'EUR',
                'availability' => 'in_stock',
                'condition' => 'new',
                'delivery_price' => '4.50',
                'delivery_time' => '2 days',
                'url' => 'https://shop.example.test/k2',
                'fetched_at' => now()->subMinute()->toISOString(),
            ],
        ]);

        (new UpdateMarketOffersJob($source->id, $log->id))->handle();

        $offer = MarketOffer::query()->sole();
        $this->assertSame($source->market_id, $offer->market_id);
        $this->assertSame($mapping->central_product_id, $offer->central_product_id);
        $this->assertSame($mapping->id, $offer->external_product_mapping_id);
        $this->assertSame('79.99', $offer->price);
        $this->assertSame('Keyboard Shop', $offer->merchant->name);
        $this->assertSame(1, $log->fresh()->items_updated);
        Bus::assertDispatched(
            StorePriceHistoryJob::class,
            fn (StorePriceHistoryJob $job): bool => $job->marketOfferId === $offer->id,
        );
    }

    public function test_does_not_publish_offer_when_mapping_is_not_approved(): void
    {
        Bus::fake();
        $source = PriceSource::factory()->manual()->create();
        $mapping = ExternalProductMapping::factory()->pending()->for($source)->create([
            'external_product_id' => 'external-pending',
            'external_sku' => 'PENDING',
        ]);
        $log = PriceSourceSyncLog::factory()->running()->for($source)->create();
        RawPriceOffer::factory()->matched()->forMapping($mapping)->create([
            'price_source_sync_log_id' => $log->id,
        ]);

        (new UpdateMarketOffersJob($source->id, $log->id))->handle();

        $this->assertSame(ExternalProductMappingStatus::Pending, $mapping->fresh()->status);
        $this->assertSame(0, MarketOffer::query()->count());
        $this->assertSame(0, $log->fresh()->items_updated);
        Bus::assertNotDispatched(StorePriceHistoryJob::class);
    }
}

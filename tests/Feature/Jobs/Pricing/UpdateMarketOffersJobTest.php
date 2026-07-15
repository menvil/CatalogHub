<?php

namespace Tests\Feature\Jobs\Pricing;

use App\Enums\ExternalProductMappingStatus;
use App\Enums\RawPriceOfferStatus;
use App\Events\MarketOfferUpdated;
use App\Jobs\Pricing\StorePriceHistoryJob;
use App\Jobs\Pricing\UpdateMarketOffersJob;
use App\Models\ExternalProductMapping;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\RawPriceOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

class UpdateMarketOffersJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_current_offer_for_approved_mapping_and_dispatches_history(): void
    {
        Bus::fake();
        Event::fake([MarketOfferUpdated::class]);
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
        Event::assertDispatched(
            MarketOfferUpdated::class,
            fn (MarketOfferUpdated $event): bool => $event->marketOfferId === $offer->id,
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

    public function test_distinct_merchant_names_with_the_same_lossy_slug_remain_distinct(): void
    {
        Bus::fake();
        $source = PriceSource::factory()->manual()->create();
        $firstMapping = ExternalProductMapping::factory()->approved()->for($source)->create([
            'external_product_id' => 'external-acme-dot',
            'external_sku' => 'ACME-DOT',
        ]);
        $secondMapping = ExternalProductMapping::factory()->approved()->for($source)->create([
            'external_product_id' => 'external-acme-space',
            'external_sku' => 'ACME-SPACE',
        ]);
        $log = PriceSourceSyncLog::factory()->running()->for($source)->create();

        foreach ([[$firstMapping, 'ACME.com'], [$secondMapping, 'ACMEcom']] as [$mapping, $merchantName]) {
            RawPriceOffer::factory()->matched()->forMapping($mapping)->create([
                'price_source_sync_log_id' => $log->id,
                'normalized_payload_json' => [
                    'merchant_name' => $merchantName,
                    'price' => '49.99',
                    'currency' => 'EUR',
                    'fetched_at' => now()->toISOString(),
                ],
            ]);
        }

        (new UpdateMarketOffersJob($source->id, $log->id))->handle();

        $this->assertSame(2, MarketMerchant::query()->count());
        $this->assertEqualsCanonicalizing(['ACME.com', 'ACMEcom'], MarketMerchant::query()->pluck('name')->all());
        $this->assertSame(2, MarketOffer::query()->count());
    }

    public function test_rejects_non_numeric_or_negative_delivery_price(): void
    {
        Bus::fake();
        $source = PriceSource::factory()->manual()->create();
        $mapping = ExternalProductMapping::factory()->approved()->for($source)->create([
            'external_product_id' => 'external-invalid-delivery',
        ]);
        $log = PriceSourceSyncLog::factory()->running()->for($source)->create();
        RawPriceOffer::factory()->matched()->forMapping($mapping)->create([
            'price_source_sync_log_id' => $log->id,
            'normalized_payload_json' => [
                'merchant_name' => 'Delivery Shop',
                'price' => '49.99',
                'currency' => 'EUR',
                'delivery_price' => 'free',
                'fetched_at' => now()->toISOString(),
            ],
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('delivery_price');

        (new UpdateMarketOffersJob($source->id, $log->id))->handle();
    }

    public function test_older_raw_offer_does_not_overwrite_current_offer_or_dispatch_history(): void
    {
        Bus::fake();
        $source = PriceSource::factory()->manual()->create();
        $mapping = ExternalProductMapping::factory()->approved()->for($source)->create([
            'external_product_id' => 'external-ordered-offer',
        ]);
        $newerLog = PriceSourceSyncLog::factory()->running()->for($source)->create();
        RawPriceOffer::factory()->matched()->forMapping($mapping)->create([
            'price_source_sync_log_id' => $newerLog->id,
            'normalized_payload_json' => [
                'merchant_name' => 'Ordered Shop',
                'price' => '99.99',
                'currency' => 'EUR',
                'fetched_at' => now()->toISOString(),
            ],
        ]);
        (new UpdateMarketOffersJob($source->id, $newerLog->id))->handle();

        Bus::fake();
        $olderLog = PriceSourceSyncLog::factory()->running()->for($source)->create();
        $olderRaw = RawPriceOffer::factory()->matched()->forMapping($mapping)->create([
            'price_source_sync_log_id' => $olderLog->id,
            'normalized_payload_json' => [
                'merchant_name' => 'Ordered Shop',
                'price' => '49.99',
                'currency' => 'EUR',
                'fetched_at' => now()->subHour()->toISOString(),
            ],
        ]);

        (new UpdateMarketOffersJob($source->id, $olderLog->id))->handle();

        $this->assertSame('99.99', MarketOffer::query()->sole()->price);
        $this->assertSame(RawPriceOfferStatus::Ignored, $olderRaw->fresh()->status);
        $this->assertSame(0, $olderLog->fresh()->items_updated);
        Bus::assertNotDispatched(StorePriceHistoryJob::class);
    }
}

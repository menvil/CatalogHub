<?php

namespace Tests\Feature\Jobs\Pricing;

use App\Enums\RawPriceOfferStatus;
use App\Jobs\Pricing\MatchExternalOffersJob;
use App\Jobs\Pricing\NormalizeExternalOffersJob;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\RawPriceOffer;
use App\Pricing\PriceSourceAdapterRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class NormalizeExternalOffersJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_normalizes_fetched_rows_marks_invalid_rows_and_dispatches_matching(): void
    {
        Bus::fake();
        $source = PriceSource::factory()->manual()->create();
        $log = PriceSourceSyncLog::factory()->running()->for($source)->create();
        $valid = RawPriceOffer::factory()->fetched()->for($source)->create([
            'price_source_sync_log_id' => $log->id,
            'raw_payload_json' => [
                'sku' => 'K2', 'title' => 'Keychron K2', 'price' => '79.99',
                'currency' => 'EUR', 'availability' => 'in stock',
            ],
        ]);
        $invalid = RawPriceOffer::factory()->fetched()->for($source)->create([
            'price_source_sync_log_id' => $log->id,
            'raw_payload_json' => ['sku' => 'BROKEN', 'currency' => 'EUR'],
        ]);

        (new NormalizeExternalOffersJob($source->id, $log->id))
            ->handle(app(PriceSourceAdapterRegistry::class));

        $this->assertSame(RawPriceOfferStatus::Normalized, $valid->fresh()->status);
        $this->assertSame('79.99', $valid->fresh()->normalized_payload_json['price']);
        $this->assertSame('K2', $valid->fresh()->external_sku);
        $this->assertSame(RawPriceOfferStatus::Failed, $invalid->fresh()->status);
        $this->assertNotNull($invalid->fresh()->error_message);
        $this->assertSame(1, $log->fresh()->items_normalized);
        Bus::assertDispatched(
            MatchExternalOffersJob::class,
            fn (MatchExternalOffersJob $job): bool => $job->priceSourceId === $source->id
                && $job->priceSourceSyncLogId === $log->id,
        );
    }
}

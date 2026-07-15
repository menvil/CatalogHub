<?php

namespace Tests\Feature\Jobs\Pricing;

use App\Enums\ExternalProductMappingStatus;
use App\Enums\RawPriceOfferStatus;
use App\Jobs\Pricing\MatchExternalOffersJob;
use App\Jobs\Pricing\UpdateMarketOffersJob;
use App\Models\ExternalProductMapping;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\RawPriceOffer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class MatchExternalOffersJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_pending_mapping_when_external_identity_is_unknown(): void
    {
        Bus::fake();
        $source = PriceSource::factory()->manual()->create();
        $log = PriceSourceSyncLog::factory()->running()->for($source)->create();
        $raw = RawPriceOffer::factory()->normalized()->for($source)->create([
            'price_source_sync_log_id' => $log->id,
            'external_product_id' => 'external-27gp850',
            'external_sku' => '27GP850-B',
            'external_title' => 'LG UltraGear 27GP850-B',
        ]);

        (new MatchExternalOffersJob($source->id, $log->id))->handle();

        $mapping = ExternalProductMapping::query()->sole();
        $this->assertSame(ExternalProductMappingStatus::Pending, $mapping->status);
        $this->assertSame('27GP850-B', $mapping->external_sku);
        $this->assertSame(RawPriceOfferStatus::Normalized, $raw->fresh()->status);
        $this->assertSame(0, $log->fresh()->items_matched);
        Bus::assertDispatched(UpdateMarketOffersJob::class);
    }

    public function test_reuses_approved_mapping_and_marks_raw_offer_matched(): void
    {
        Bus::fake();
        $source = PriceSource::factory()->manual()->create();
        $mapping = ExternalProductMapping::factory()->approved()->for($source)->create([
            'external_product_id' => 'external-k2',
            'external_sku' => 'K2',
        ]);
        $log = PriceSourceSyncLog::factory()->running()->for($source)->create();
        $raw = RawPriceOffer::factory()->normalized()->for($source)->create([
            'price_source_sync_log_id' => $log->id,
            'external_product_id' => 'external-k2',
            'external_sku' => 'K2',
        ]);

        (new MatchExternalOffersJob($source->id, $log->id))->handle();

        $this->assertSame(1, ExternalProductMapping::query()->count());
        $this->assertSame(RawPriceOfferStatus::Matched, $raw->fresh()->status);
        $this->assertSame(1, $log->fresh()->items_matched);
    }
}

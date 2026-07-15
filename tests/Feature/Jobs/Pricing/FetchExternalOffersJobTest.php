<?php

namespace Tests\Feature\Jobs\Pricing;

use App\Enums\PriceSourceType;
use App\Jobs\Pricing\FetchExternalOffersJob;
use App\Jobs\Pricing\NormalizeExternalOffersJob;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\RawPriceOffer;
use App\Pricing\PriceSourceAdapterRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use InvalidArgumentException;
use Tests\TestCase;

class FetchExternalOffersJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_job_stores_raw_offers_updates_counter_and_dispatches_normalization(): void
    {
        Bus::fake();
        $source = PriceSource::factory()->manual()->create([
            'config_json' => [
                'offers' => [['sku' => 'K2', 'price' => 79.99, 'currency' => 'EUR']],
            ],
        ]);
        $log = PriceSourceSyncLog::factory()->for($source)->create();

        (new FetchExternalOffersJob($source->id, $log->id))
            ->handle(app(PriceSourceAdapterRegistry::class));

        $raw = RawPriceOffer::query()->sole();
        $this->assertSame('K2', $raw->external_sku);
        $this->assertSame('79.99', number_format((float) $raw->raw_payload_json['price'], 2, '.', ''));
        $this->assertSame(1, $log->fresh()->items_fetched);
        $this->assertSame('running', $log->fresh()->getRawOriginal('status'));
        Bus::assertDispatched(
            NormalizeExternalOffersJob::class,
            fn (NormalizeExternalOffersJob $job): bool => $job->priceSourceId === $source->id
                && $job->priceSourceSyncLogId === $log->id,
        );
    }

    public function test_fetch_failure_is_written_to_sync_log(): void
    {
        $source = PriceSource::factory()->create(['type' => PriceSourceType::Widget]);
        $log = PriceSourceSyncLog::factory()->for($source)->create();

        try {
            (new FetchExternalOffersJob($source->id, $log->id))
                ->handle(app(PriceSourceAdapterRegistry::class));
            $this->fail('Expected unsupported adapter exception.');
        } catch (InvalidArgumentException) {
            $this->assertSame('failed', $log->fresh()->getRawOriginal('status'));
            $this->assertNotNull($log->fresh()->finished_at);
            $this->assertNotNull($log->fresh()->error_message);
        }
    }

    public function test_fetch_replay_replaces_raw_rows_for_the_same_sync_log(): void
    {
        Bus::fake();
        $source = PriceSource::factory()->manual()->create([
            'config_json' => [
                'offers' => [['sku' => 'K2', 'price' => 79.99, 'currency' => 'EUR']],
            ],
        ]);
        $log = PriceSourceSyncLog::factory()->for($source)->create();
        $job = new FetchExternalOffersJob($source->id, $log->id);

        $job->handle(app(PriceSourceAdapterRegistry::class));
        RawPriceOffer::query()->sole()->update(['status' => 'normalized']);
        $job->handle(app(PriceSourceAdapterRegistry::class));

        $this->assertSame(1, RawPriceOffer::query()->where('price_source_sync_log_id', $log->id)->count());
        $this->assertSame('fetched', RawPriceOffer::query()->sole()->getRawOriginal('status'));
        $this->assertSame(1, $log->fresh()->items_fetched);
    }
}

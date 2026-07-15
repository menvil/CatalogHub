<?php

namespace Tests\Feature\Pricing;

use App\Jobs\Pricing\FetchExternalOffersJob;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Pricing\Adapters\ManualOfferAdapter;
use App\Pricing\PriceSourceAdapterRegistry;
use App\Services\Pricing\PriceSourceSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class PriceSourceSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_queued_log_and_dispatches_fetch_job(): void
    {
        Bus::fake();
        $source = PriceSource::factory()->active()->create();

        $log = app(PriceSourceSyncService::class)->sync($source);

        $this->assertInstanceOf(PriceSourceSyncLog::class, $log);
        $this->assertSame('queued', $log->getRawOriginal('status'));
        $this->assertTrue($source->is($log->priceSource));
        Bus::assertDispatched(
            FetchExternalOffersJob::class,
            fn (FetchExternalOffersJob $job): bool => $job->priceSourceId === $source->id
                && $job->priceSourceSyncLogId === $log->id,
        );
    }

    public function test_adapter_registry_resolves_supported_adapter_without_source_specific_service_logic(): void
    {
        $source = PriceSource::factory()->manual()->create();

        $this->assertInstanceOf(
            ManualOfferAdapter::class,
            app(PriceSourceAdapterRegistry::class)->for($source),
        );
    }
}

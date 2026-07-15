<?php

namespace App\Jobs\Pricing;

use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\RawPriceOffer;
use App\Pricing\PriceSourceAdapterRegistry;
use App\Services\Pricing\PriceSourceSyncStatusService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

final class FetchExternalOffersJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public int $priceSourceId,
        public int $priceSourceSyncLogId,
    ) {}

    public function handle(
        PriceSourceAdapterRegistry $adapterRegistry,
        ?PriceSourceSyncStatusService $statusService = null,
    ): void {
        $statusService ??= app(PriceSourceSyncStatusService::class);
        $source = PriceSource::query()->findOrFail($this->priceSourceId);
        $log = PriceSourceSyncLog::query()->findOrFail($this->priceSourceSyncLogId);

        try {
            $statusService->start($source, $log);

            $result = $adapterRegistry->for($source)->fetchOffers($source);

            DB::transaction(function () use ($source, $log, $result): void {
                foreach ($result->offers as $payload) {
                    RawPriceOffer::query()->create([
                        'price_source_id' => $source->id,
                        'price_source_sync_log_id' => $log->id,
                        'external_product_id' => $this->payloadString($payload, 'external_product_id'),
                        'external_sku' => $this->payloadString($payload, 'external_sku')
                            ?? $this->payloadString($payload, 'sku'),
                        'external_title' => $this->payloadString($payload, 'external_title')
                            ?? $this->payloadString($payload, 'title'),
                        'raw_payload_json' => $payload,
                        'status' => 'fetched',
                        'fetched_at' => now(),
                    ]);
                }

                $metadata = $log->metadata ?? [];

                if ($result->metadata !== []) {
                    $metadata['fetch'] = $result->metadata;
                }

                $log->update([
                    'items_fetched' => count($result->offers),
                    'metadata' => $metadata,
                ]);
            });

            NormalizeExternalOffersJob::dispatch($source->id, $log->id)->afterCommit();
        } catch (Throwable $exception) {
            $statusService->fail($source, $log, $exception->getMessage(), ['stage' => 'fetch']);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->markFailed($exception ?? new \RuntimeException('Price source fetch failed.'), 'fetch');
    }

    /** @param array<string, mixed> $payload */
    private function payloadString(array $payload, string $key): ?string
    {
        $value = $payload[$key] ?? null;

        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }

    private function markFailed(Throwable $exception, string $stage): void
    {
        $source = PriceSource::query()->find($this->priceSourceId);
        $log = PriceSourceSyncLog::query()->find($this->priceSourceSyncLogId);

        if ($source instanceof PriceSource && $log instanceof PriceSourceSyncLog) {
            app(PriceSourceSyncStatusService::class)->fail(
                $source,
                $log,
                $exception->getMessage(),
                ['stage' => $stage],
            );
        }
    }
}

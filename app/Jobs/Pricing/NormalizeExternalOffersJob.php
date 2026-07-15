<?php

namespace App\Jobs\Pricing;

use App\Enums\RawPriceOfferStatus;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\RawPriceOffer;
use App\Pricing\PriceSourceAdapterRegistry;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class NormalizeExternalOffersJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public int $priceSourceId,
        public int $priceSourceSyncLogId,
    ) {}

    public function handle(PriceSourceAdapterRegistry $adapterRegistry): void
    {
        $source = PriceSource::query()->findOrFail($this->priceSourceId);
        $log = PriceSourceSyncLog::query()->findOrFail($this->priceSourceSyncLogId);

        try {
            $adapter = $adapterRegistry->for($source);
            $rows = RawPriceOffer::query()
                ->where('price_source_id', $source->id)
                ->where('price_source_sync_log_id', $log->id)
                ->where('status', RawPriceOfferStatus::Fetched->value)
                ->get();

            foreach ($rows as $row) {
                try {
                    $normalized = $adapter->normalizeOffer($source, $row->raw_payload_json);
                    $row->update([
                        'external_product_id' => $normalized->externalProductId,
                        'external_sku' => $normalized->externalSku,
                        'external_title' => $normalized->externalTitle,
                        'normalized_payload_json' => $normalized->toArray(),
                        'status' => RawPriceOfferStatus::Normalized,
                        'error_message' => null,
                    ]);
                } catch (Throwable $exception) {
                    $row->update([
                        'status' => RawPriceOfferStatus::Failed,
                        'error_message' => $exception->getMessage(),
                    ]);
                }
            }

            $log->update([
                'items_normalized' => RawPriceOffer::query()
                    ->where('price_source_id', $source->id)
                    ->where('price_source_sync_log_id', $log->id)
                    ->where('status', RawPriceOfferStatus::Normalized->value)
                    ->count(),
            ]);

            MatchExternalOffersJob::dispatch($source->id, $log->id)->afterCommit();
        } catch (Throwable $exception) {
            $this->markFailed($exception);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->markFailed($exception ?? new \RuntimeException('Price source normalization failed.'));
    }

    private function markFailed(Throwable $exception): void
    {
        PriceSourceSyncLog::query()->whereKey($this->priceSourceSyncLogId)->update([
            'status' => 'failed',
            'finished_at' => now(),
            'error_message' => $exception->getMessage(),
        ]);
    }
}

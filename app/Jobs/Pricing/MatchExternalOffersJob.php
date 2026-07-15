<?php

namespace App\Jobs\Pricing;

use App\Enums\ExternalProductMappingStatus;
use App\Enums\RawPriceOfferStatus;
use App\Models\ExternalProductMapping;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\RawPriceOffer;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class MatchExternalOffersJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public int $priceSourceId,
        public int $priceSourceSyncLogId,
    ) {}

    public function handle(): void
    {
        $source = PriceSource::query()->findOrFail($this->priceSourceId);
        $log = PriceSourceSyncLog::query()->findOrFail($this->priceSourceSyncLogId);

        try {
            $rows = RawPriceOffer::query()
                ->where('price_source_id', $source->id)
                ->where('price_source_sync_log_id', $log->id)
                ->where('status', RawPriceOfferStatus::Normalized->value)
                ->get();

            foreach ($rows as $row) {
                $mapping = $this->mappingFor($row);

                if (
                    $mapping->status === ExternalProductMappingStatus::Approved
                    && $mapping->central_product_id !== null
                ) {
                    $row->update([
                        'status' => RawPriceOfferStatus::Matched,
                        'error_message' => null,
                    ]);
                }
            }

            $log->update([
                'items_matched' => RawPriceOffer::query()
                    ->where('price_source_id', $source->id)
                    ->where('price_source_sync_log_id', $log->id)
                    ->where('status', RawPriceOfferStatus::Matched->value)
                    ->count(),
            ]);

            UpdateMarketOffersJob::dispatch($source->id, $log->id)->afterCommit();
        } catch (Throwable $exception) {
            $this->markFailed($exception);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->markFailed($exception ?? new \RuntimeException('Price source matching failed.'));
    }

    private function mappingFor(RawPriceOffer $row): ExternalProductMapping
    {
        $mapping = null;

        if (filled($row->external_product_id)) {
            $mapping = ExternalProductMapping::query()
                ->where('price_source_id', $row->price_source_id)
                ->where('external_product_id', $row->external_product_id)
                ->first();
        }

        if ($mapping === null && filled($row->external_sku)) {
            $mapping = ExternalProductMapping::query()
                ->where('price_source_id', $row->price_source_id)
                ->where('external_sku', $row->external_sku)
                ->first();
        }

        if ($mapping === null) {
            $mapping = $this->createPendingMapping($row);
        }

        $normalized = $row->normalized_payload_json ?? [];
        $changes = [];

        if (blank($mapping->external_title) && filled($row->external_title)) {
            $changes['external_title'] = $row->external_title;
        }

        if (blank($mapping->external_url) && filled($normalized['url'] ?? null)) {
            $changes['external_url'] = $normalized['url'];
        }

        if ($changes !== []) {
            $mapping->update($changes);
        }

        $mapping->refresh();

        return $mapping;
    }

    private function createPendingMapping(RawPriceOffer $row): ExternalProductMapping
    {
        $normalized = $row->normalized_payload_json ?? [];
        $values = [
            'external_product_id' => $row->external_product_id,
            'external_sku' => $row->external_sku,
            'external_title' => $row->external_title,
            'external_url' => $normalized['url'] ?? null,
            'status' => ExternalProductMappingStatus::Pending,
            'metadata' => array_filter([
                'brand_name' => $normalized['brand_name'] ?? null,
                'model_name' => $normalized['model_name'] ?? null,
            ]),
        ];

        if (filled($row->external_product_id)) {
            return ExternalProductMapping::query()->firstOrCreate([
                'price_source_id' => $row->price_source_id,
                'external_product_id' => $row->external_product_id,
            ], $values);
        }

        if (filled($row->external_sku)) {
            return ExternalProductMapping::query()->firstOrCreate([
                'price_source_id' => $row->price_source_id,
                'external_sku' => $row->external_sku,
            ], $values);
        }

        return ExternalProductMapping::query()->create([
            'price_source_id' => $row->price_source_id,
            ...$values,
        ]);
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

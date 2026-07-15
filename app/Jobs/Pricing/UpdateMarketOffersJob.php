<?php

namespace App\Jobs\Pricing;

use App\Enums\ExternalProductMappingStatus;
use App\Enums\MarketMerchantStatus;
use App\Enums\MarketOfferStatus;
use App\Enums\OfferAvailability;
use App\Enums\OfferCondition;
use App\Enums\RawPriceOfferStatus;
use App\Models\ExternalProductMapping;
use App\Models\MarketMerchant;
use App\Models\MarketOffer;
use App\Models\PriceSource;
use App\Models\PriceSourceSyncLog;
use App\Models\RawPriceOffer;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

final class UpdateMarketOffersJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public int $priceSourceId,
        public int $priceSourceSyncLogId,
    ) {}

    public function handle(): void
    {
        $source = PriceSource::query()->with('market')->findOrFail($this->priceSourceId);
        $log = PriceSourceSyncLog::query()->findOrFail($this->priceSourceSyncLogId);

        try {
            $offerIds = DB::transaction(function () use ($source, $log): array {
                $offerIds = [];
                $rows = RawPriceOffer::query()
                    ->where('price_source_id', $source->id)
                    ->where('price_source_sync_log_id', $log->id)
                    ->where('status', RawPriceOfferStatus::Matched->value)
                    ->orderBy('fetched_at')
                    ->orderBy('id')
                    ->get();

                foreach ($rows as $row) {
                    $mapping = $this->approvedMappingFor($row);

                    if ($mapping === null) {
                        continue;
                    }

                    $normalized = $row->normalized_payload_json ?? [];
                    $merchant = $this->merchant($source->market_id, $normalized['merchant_name'] ?? $source->name);
                    $offer = $this->upsertOffer($source, $mapping, $merchant, $row, $normalized);
                    $offerIds[$offer->id] = $offer->id;
                }

                $log->update(['items_updated' => count($offerIds)]);

                return array_values($offerIds);
            });

            foreach ($offerIds as $offerId) {
                StorePriceHistoryJob::dispatch($offerId)->afterCommit();
            }
        } catch (Throwable $exception) {
            $this->markFailed($exception);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        $this->markFailed($exception ?? new \RuntimeException('Market offer update failed.'));
    }

    private function approvedMappingFor(RawPriceOffer $row): ?ExternalProductMapping
    {
        $query = ExternalProductMapping::query()
            ->where('price_source_id', $row->price_source_id);

        $mapping = filled($row->external_product_id)
            ? (clone $query)->where('external_product_id', $row->external_product_id)->first()
            : null;

        if ($mapping === null && filled($row->external_sku)) {
            $mapping = (clone $query)->where('external_sku', $row->external_sku)->first();
        }

        return $mapping?->status === ExternalProductMappingStatus::Approved
            && $mapping->central_product_id !== null
                ? $mapping
                : null;
    }

    private function merchant(int $marketId, mixed $name): MarketMerchant
    {
        $name = is_scalar($name) ? trim((string) $name) : '';

        if ($name === '') {
            throw new InvalidArgumentException('Normalized offer merchant_name is required.');
        }

        $slug = Str::slug($name);
        $slug = $slug !== '' ? $slug : 'merchant-'.substr(hash('sha256', $name), 0, 12);

        return MarketMerchant::query()->firstOrCreate([
            'market_id' => $marketId,
            'slug' => $slug,
        ], [
            'name' => $name,
            'status' => MarketMerchantStatus::Active,
            'metadata' => [],
        ]);
    }

    /** @param array<string, mixed> $normalized */
    private function upsertOffer(
        PriceSource $source,
        ExternalProductMapping $mapping,
        MarketMerchant $merchant,
        RawPriceOffer $row,
        array $normalized,
    ): MarketOffer {
        $price = $normalized['price'] ?? null;
        $currency = strtoupper((string) ($normalized['currency'] ?? ''));

        if (! is_numeric($price) || (float) $price < 0 || strlen($currency) !== 3) {
            throw new InvalidArgumentException('Matched normalized offer requires valid price and currency.');
        }

        $availability = OfferAvailability::tryFrom((string) ($normalized['availability'] ?? ''))
            ?? OfferAvailability::Unknown;
        $condition = OfferCondition::tryFrom((string) ($normalized['condition'] ?? ''))
            ?? OfferCondition::Unknown;
        $fetchedAt = filled($normalized['fetched_at'] ?? null)
            ? CarbonImmutable::parse((string) $normalized['fetched_at'])
            : CarbonImmutable::instance($row->fetched_at);

        return MarketOffer::query()->updateOrCreate([
            'market_merchant_id' => $merchant->id,
            'central_product_id' => $mapping->central_product_id,
            'price_source_id' => $source->id,
        ], [
            'market_id' => $source->market_id,
            'external_product_mapping_id' => $mapping->id,
            'price' => number_format((float) $price, 2, '.', ''),
            'currency' => $currency,
            'availability' => $availability,
            'condition' => $condition,
            'delivery_price' => filled($normalized['delivery_price'] ?? null)
                ? number_format((float) $normalized['delivery_price'], 2, '.', '')
                : null,
            'delivery_time' => $this->scalarOrNull($normalized['delivery_time'] ?? null),
            'url' => $this->scalarOrNull($normalized['url'] ?? null),
            'last_seen_at' => $fetchedAt,
            'last_checked_at' => now(),
            'status' => MarketOfferStatus::Active,
            'metadata' => [
                'last_raw_price_offer_id' => $row->id,
                'brand_name' => $normalized['brand_name'] ?? null,
                'model_name' => $normalized['model_name'] ?? null,
            ],
        ]);
    }

    private function scalarOrNull(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
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

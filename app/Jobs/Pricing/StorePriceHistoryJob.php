<?php

namespace App\Jobs\Pricing;

use App\Models\MarketOffer;
use App\Models\PriceHistory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

final class StorePriceHistoryJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(
        public int $marketOfferId,
        public bool $force = false,
    ) {}

    public function handle(): void
    {
        $offer = MarketOffer::query()->findOrFail($this->marketOfferId);
        $latest = PriceHistory::query()
            ->where('market_offer_id', $offer->id)
            ->latest('checked_at')
            ->latest('id')
            ->first();

        if (! $this->force && $latest !== null && $this->sameSnapshot($offer, $latest)) {
            return;
        }

        PriceHistory::query()->create([
            'market_offer_id' => $offer->id,
            'price' => $offer->price,
            'currency' => $offer->currency,
            'availability' => $offer->availability,
            'condition' => $offer->condition,
            'delivery_price' => $offer->delivery_price,
            'checked_at' => $offer->last_checked_at ?? now(),
            'source_snapshot_json' => [
                'price_source_id' => $offer->price_source_id,
                'external_product_mapping_id' => $offer->external_product_mapping_id,
                'status' => $offer->status->value,
                'url' => $offer->url,
                'last_seen_at' => $offer->last_seen_at->toISOString(),
            ],
        ]);
    }

    private function sameSnapshot(MarketOffer $offer, PriceHistory $history): bool
    {
        return $history->price === $offer->price
            && $history->currency === $offer->currency
            && $history->availability === $offer->availability
            && $history->condition === $offer->condition
            && $history->delivery_price === $offer->delivery_price;
    }
}

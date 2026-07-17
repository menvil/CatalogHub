<?php

namespace App\Actions\Pricing;

use App\Models\MarketOffer;
use App\Models\OfferClick;
use App\Models\Site;

final class RecordOfferClickAction
{
    public function handle(
        Site $site,
        MarketOffer $offer,
        int|string|null $userId,
        ?string $sessionId,
        ?string $ipAddress,
        ?string $userAgent,
    ): OfferClick {
        return OfferClick::query()->create([
            'site_id' => $site->id,
            'market_offer_id' => $offer->id,
            'central_product_id' => $offer->central_product_id,
            'merchant_id' => $offer->market_merchant_id,
            'user_id' => $userId,
            'session_id' => $sessionId,
            'ip_hash' => $this->privacyHash($ipAddress),
            'user_agent_hash' => $this->privacyHash($userAgent),
            'clicked_at' => now(),
        ]);
    }

    private function privacyHash(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}

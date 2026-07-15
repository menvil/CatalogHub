<?php

namespace App\Http\Controllers\Public;

use App\Domains\PublicSite\SiteContextResolver;
use App\Http\Controllers\Controller;
use App\Models\MarketOffer;
use App\Models\OfferClick;
use App\Services\Pricing\ValidMarketOfferQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TrackOfferClickController extends Controller
{
    public function __invoke(
        Request $request,
        MarketOffer $offer,
        SiteContextResolver $sites,
        ValidMarketOfferQuery $validOffers,
    ): RedirectResponse {
        $site = $sites->resolveHost($request->getHost());
        $offer = $validOffers->forSite($site)->whereKey($offer->getKey())->firstOrFail();
        $destination = $this->safeDestination($offer->url);

        abort_if($destination === null, 404);

        OfferClick::query()->create([
            'site_id' => $site->id,
            'market_offer_id' => $offer->id,
            'central_product_id' => $offer->central_product_id,
            'merchant_id' => $offer->market_merchant_id,
            'user_id' => $request->user()?->getAuthIdentifier(),
            'session_id' => $request->session()->getId(),
            'ip_hash' => $this->privacyHash($request->ip()),
            'user_agent_hash' => $this->privacyHash($request->userAgent()),
            'clicked_at' => now(),
        ]);

        return redirect()->away($destination);
    }

    private function safeDestination(?string $url): ?string
    {
        if ($url === null || filter_var($url, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }

    private function privacyHash(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return hash_hmac('sha256', $value, (string) config('app.key'));
    }
}

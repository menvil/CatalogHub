<?php

namespace App\Http\Controllers\Public;

use App\Actions\Pricing\RecordOfferClickAction;
use App\Domains\PublicSite\SiteContextResolver;
use App\Http\Controllers\Controller;
use App\Models\MarketOffer;
use App\Queries\Pricing\ValidMarketOfferQuery;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TrackOfferClickController extends Controller
{
    public function __invoke(
        Request $request,
        MarketOffer $offer,
        SiteContextResolver $sites,
        ValidMarketOfferQuery $validOffers,
        RecordOfferClickAction $recordClick,
    ): RedirectResponse {
        $site = $sites->resolveHost($request->getHost());
        $offer = $validOffers->findForSite($site, $offer);
        $destination = $this->safeDestination($offer->url);

        abort_if($destination === null, 404);

        $recordClick->handle(
            $site,
            $offer,
            $request->user()?->getAuthIdentifier(),
            $request->session()->getId(),
            $request->ip(),
            $request->userAgent(),
        );

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
}

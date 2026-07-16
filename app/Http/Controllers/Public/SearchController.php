<?php

namespace App\Http\Controllers\Public;

use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use App\Models\SiteSearchDocument;
use App\Queries\PublicSite\PublicProductSearchQuery;
use App\Services\Pricing\ProductCardPricePresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class SearchController extends Controller
{
    public function __invoke(
        Request $request,
        string $locale,
        SiteContextResolver $sites,
        ThemeLayoutResolver $layouts,
        LocalizedUrlResolver $urls,
        ProductCardPricePresenter $pricePresenter,
        PublicProductSearchQuery $search,
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $site->loadMissing('market');
        $term = trim($request->string('q')->toString());
        $results = collect();

        if ($term !== '') {
            $results = $search->search($site, $locale, $term)
                ->map(fn (SiteSearchDocument $document): array => [
                    'title' => $document->title,
                    'slug' => $document->slug,
                    'url' => $urls->product($site, $locale, (string) $document->slug),
                    'payload' => $document->payload_json ?? [],
                    'price' => $pricePresenter->present($document, $site->market->currency_code, $locale),
                ]);
        }

        return view($layouts->resolve($site, 'search'), [
            'site' => $site,
            'locale' => $locale,
            'term' => $term,
            'results' => $results,
            'homeUrl' => $urls->home($site, $locale),
        ]);
    }
}

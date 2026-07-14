<?php

namespace App\Http\Controllers\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use App\Models\SiteSearchDocument;
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
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $term = trim($request->string('q')->toString());
        $results = collect();

        if ($term !== '') {
            $escapedTerm = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term);
            $pattern = "%{$escapedTerm}%";
            $results = SiteSearchDocument::query()
                ->where('site_id', $site->id)
                ->where('locale', $locale)
                ->where('document_type', 'product')
                ->where('status', ProjectionStatus::Active)
                ->where(function ($query) use ($pattern): void {
                    $query->whereRaw("search_text LIKE ? ESCAPE '!'", [$pattern])
                        ->orWhereRaw("title LIKE ? ESCAPE '!'", [$pattern]);
                })
                ->orderBy('title')
                ->limit(24)
                ->get()
                ->map(fn (SiteSearchDocument $document): array => [
                    'title' => $document->title,
                    'slug' => $document->slug,
                    'url' => $urls->product($site, $locale, (string) $document->slug),
                    'payload' => $document->payload_json ?? [],
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

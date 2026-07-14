<?php

namespace App\Http\Controllers\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
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
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $term = trim($request->string('q')->toString());
        $results = collect();

        if ($term !== '') {
            $results = SiteSearchDocument::query()
                ->where('site_id', $site->id)
                ->where('locale', $locale)
                ->where('document_type', 'product')
                ->where('status', ProjectionStatus::Active)
                ->where(function ($query) use ($term): void {
                    $query->where('search_text', 'like', "%{$term}%")
                        ->orWhere('title', 'like', "%{$term}%");
                })
                ->orderBy('title')
                ->limit(24)
                ->get()
                ->map(fn (SiteSearchDocument $document): array => [
                    'title' => $document->title,
                    'slug' => $document->slug,
                    'url' => "/{$locale}/products/{$document->slug}",
                    'payload' => $document->payload_json ?? [],
                ]);
        }

        return view($layouts->resolve($site, 'search'), [
            'site' => $site,
            'locale' => $locale,
            'term' => $term,
            'results' => $results,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Public;

use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use App\Models\ContentTranslation;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

final class ContentController extends Controller
{
    public function __invoke(
        Request $request,
        string $locale,
        string $slug,
        SiteContextResolver $sites,
        ThemeLayoutResolver $layouts,
        LocalizedUrlResolver $urls,
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $translation = ContentTranslation::query()
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('status', 'published')
            ->whereHas('contentItem', fn (Builder $query): Builder => $query
                ->where('site_id', $site->id)
                ->where('status', 'published'))
            ->with('contentItem')
            ->first();

        abort_unless($translation instanceof ContentTranslation, 404);

        return view($layouts->resolve($site, 'article'), [
            'site' => $site,
            'locale' => $locale,
            'contentItem' => $translation->contentItem,
            'translation' => $translation,
            'seo' => [
                'meta_title' => $translation->seoTitle(),
                'meta_description' => $translation->seoDescription(),
                'og_title' => $translation->openGraphTitle(),
                'og_description' => $translation->openGraphDescription(),
                'canonical_url' => $request->url(),
            ],
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => $urls->home($site, $locale)],
                ['label' => $translation->title, 'url' => null],
            ],
        ]);
    }
}

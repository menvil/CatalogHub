<?php

namespace App\Http\Controllers\Public;

use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use App\Models\ContentTranslation;
use App\Queries\PublicSite\PublishedContentQuery;
use App\Support\Sites\SiteRuntimeContext;
use Illuminate\Contracts\View\View;

final class ContentController extends Controller
{
    public function __invoke(
        string $locale,
        string $slug,
        SiteRuntimeContext $context,
        ThemeLayoutResolver $layouts,
        LocalizedUrlResolver $urls,
        PublishedContentQuery $content,
    ): View {
        $site = $context->site;
        $locale = $context->resolvedLocale;
        $translation = $content->find($site, $locale, $slug);

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
                'canonical_url' => $urls->article($site, $locale, $slug),
            ],
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => $urls->home($site, $locale)],
                ['label' => $translation->title, 'url' => null],
            ],
        ]);
    }
}

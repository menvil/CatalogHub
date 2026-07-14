<?php

namespace App\Http\Controllers\Public;

use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ArticleStubController extends Controller
{
    /** @var array<string, array{title: string, excerpt: string, body: list<string>}> */
    private const ARTICLES = [
        'how-to-choose-a-monitor' => [
            'title' => 'How to choose a monitor',
            'excerpt' => 'A demo editorial template for the future CatalogHub Content Engine.',
            'body' => [
                'Start with the work you do most often, then compare panel size, resolution, refresh rate, and connectivity.',
                'The product data shown elsewhere on this demo site comes from localized site projections.',
            ],
        ],
    ];

    public function __invoke(
        Request $request,
        string $locale,
        string $slug,
        SiteContextResolver $sites,
        ThemeLayoutResolver $layouts,
        LocalizedUrlResolver $urls,
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        abort_unless(isset(self::ARTICLES[$slug]), 404);

        return view($layouts->resolve($site, 'article'), [
            'site' => $site,
            'locale' => $locale,
            'article' => [...self::ARTICLES[$slug], 'slug' => $slug],
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => $urls->home($site, $locale)],
                ['label' => self::ARTICLES[$slug]['title'], 'url' => null],
            ],
        ]);
    }
}

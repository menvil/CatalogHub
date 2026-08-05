<?php

namespace App\Http\Controllers\Public;

use App\Domains\PublicSite\HomepageBlockRenderer;
use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Http\Controllers\Controller;
use App\Support\Sites\SiteRuntimeContext;
use App\Support\Themes\PublicThemeContext;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __invoke(
        string $locale,
        SiteRuntimeContext $context,
        PublicThemeContext $theme,
        HomepageBlockRenderer $blocks,
        LocalizedUrlResolver $urls,
    ): View {
        $site = $context->site;
        $locale = $context->resolvedLocale;
        $seo = data_get($site->settings_json, 'seo', []);
        $seo = is_array($seo) ? $seo : [];
        $seo['meta_title'] ??= $site->name;
        $seo['canonical_url'] ??= $urls->home($site, $locale);

        return view($theme->shellView(), [
            'site' => $site,
            'locale' => $locale,
            'blocks' => $blocks->render($site, $locale),
            'seo' => $seo,
            'theme' => $theme,
            'publicNavigation' => [
                'home' => $urls->home($site, $locale),
                'search' => $urls->search($site, $locale),
            ],
        ]);
    }
}

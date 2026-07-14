<?php

namespace App\Http\Controllers\Public;

use App\Domains\PublicSite\HomepageBlockRenderer;
use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class HomeController extends Controller
{
    public function __invoke(
        Request $request,
        string $locale,
        SiteContextResolver $sites,
        ThemeLayoutResolver $layouts,
        HomepageBlockRenderer $blocks,
        LocalizedUrlResolver $urls,
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $seo = data_get($site->settings_json, 'seo', []);
        $seo = is_array($seo) ? $seo : [];
        $seo['meta_title'] ??= $site->name;
        $seo['canonical_url'] ??= $urls->home($site, $locale);

        return view($layouts->resolve($site, 'home'), [
            'site' => $site,
            'locale' => $locale,
            'blocks' => $blocks->render($site, $locale),
            'seo' => $seo,
        ]);
    }
}

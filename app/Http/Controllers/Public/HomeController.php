<?php

namespace App\Http\Controllers\Public;

use App\Domains\PublicSite\HomepageBlockRenderer;
use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use App\Support\Sites\SiteRuntimeContext;
use Illuminate\Contracts\View\View;

final class HomeController extends Controller
{
    public function __invoke(
        string $locale,
        SiteRuntimeContext $context,
        ThemeLayoutResolver $layouts,
        HomepageBlockRenderer $blocks,
        LocalizedUrlResolver $urls,
    ): View {
        $site = $context->site;
        $locale = $context->resolvedLocale;
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

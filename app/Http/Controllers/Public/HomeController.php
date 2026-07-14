<?php

namespace App\Http\Controllers\Public;

use App\Domains\PublicSite\HomepageBlockRenderer;
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
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);

        return view($layouts->resolve($site, 'home'), [
            'site' => $site,
            'locale' => $locale,
            'blocks' => $blocks->render($site, $locale),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Public;

use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use App\Models\SiteHomeBlock;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class HomeController extends Controller
{
    public function __invoke(
        Request $request,
        string $locale,
        SiteContextResolver $sites,
        ThemeLayoutResolver $layouts,
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $heroBlock = $site->homeBlocks()
            ->where('block_code', 'hero_search')
            ->where('enabled', true)
            ->first();
        $hero = $heroBlock instanceof SiteHomeBlock ? ($heroBlock->config_json ?? []) : [];

        return view($layouts->resolve($site, 'home'), [
            'site' => $site,
            'locale' => $locale,
            'hero' => $hero,
        ]);
    }
}

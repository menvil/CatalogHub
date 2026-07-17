<?php

namespace App\Http\Controllers\Public;

use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use App\Queries\PublicSite\PublicCategoryQuery;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class CategoryController extends Controller
{
    public function show(
        Request $request,
        string $locale,
        string $slug,
        SiteContextResolver $sites,
        ThemeLayoutResolver $layouts,
        LocalizedUrlResolver $urls,
        PublicCategoryQuery $categories,
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $projection = $categories->findActive($site, $locale, $slug);
        $projectionSeo = $projection->seo_json;
        $seo = is_array($projectionSeo) ? $projectionSeo : [];
        $seo = array_replace([
            'meta_title' => $projection->title,
            'meta_description' => data_get($projection->payload_json, 'category.description'),
            'canonical_url' => $urls->category($site, $locale, $projection),
        ], array_filter($seo, fn (mixed $value): bool => $value !== null));

        return view($layouts->resolve($site, 'category'), [
            'site' => $site,
            'locale' => $locale,
            'category' => [
                'title' => $projection->title,
                'slug' => $projection->slug,
                'description' => data_get($projection->payload_json, 'category.description'),
                'intro' => data_get($projection->payload_json, 'category.intro_text'),
            ],
            'listingUrl' => $urls->listing($site, $locale, $projection),
            'seo' => $seo,
            'breadcrumbs' => [
                ['label' => 'Home', 'url' => $urls->home($site, $locale)],
                ['label' => $projection->title, 'url' => null],
            ],
        ]);
    }
}

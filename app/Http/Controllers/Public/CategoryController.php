<?php

namespace App\Http\Controllers\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use App\Models\SiteCategoryProjection;
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
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $projection = SiteCategoryProjection::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('status', ProjectionStatus::Active)
            ->firstOrFail();

        return view($layouts->resolve($site, 'category'), [
            'site' => $site,
            'locale' => $locale,
            'category' => [
                'title' => $projection->title,
                'slug' => $projection->slug,
                'description' => data_get($projection->payload_json, 'category.description'),
                'intro' => data_get($projection->payload_json, 'category.intro_text'),
                'seo' => $projection->seo_json ?? [],
            ],
            'listingUrl' => $urls->listing($site, $locale, $projection),
        ]);
    }
}

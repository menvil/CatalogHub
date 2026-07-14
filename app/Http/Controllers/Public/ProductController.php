<?php

namespace App\Http\Controllers\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use App\Models\SiteProductProjection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ProductController extends Controller
{
    public function show(
        Request $request,
        string $locale,
        string $slug,
        SiteContextResolver $sites,
        ThemeLayoutResolver $layouts,
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $projection = SiteProductProjection::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('status', ProjectionStatus::Active)
            ->firstOrFail();
        $payload = $projection->payload_json ?? [];
        $productPayload = data_get($payload, 'product', []);

        return view($layouts->resolve($site, 'product'), [
            'site' => $site,
            'locale' => $locale,
            'product' => [
                ...(is_array($productPayload) ? $productPayload : []),
                'title' => $projection->title,
                'slug' => $projection->slug,
            ],
            'brand' => is_array(data_get($payload, 'brand')) ? data_get($payload, 'brand') : null,
            'category' => is_array(data_get($payload, 'category')) ? data_get($payload, 'category') : null,
            'specSections' => is_array(data_get($payload, 'spec_sections')) ? data_get($payload, 'spec_sections') : [],
            'benefits' => is_array(data_get($payload, 'benefits')) ? data_get($payload, 'benefits') : [],
            'rating' => is_array(data_get($payload, 'rating')) ? data_get($payload, 'rating') : null,
            'media' => $projection->media_json ?? [],
            'seo' => $projection->seo_json ?? [],
        ]);
    }
}

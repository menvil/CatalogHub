<?php

namespace App\Http\Controllers\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\PublicSite\LocalizedUrlResolver;
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
        LocalizedUrlResolver $urls,
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
        $benefitPayload = data_get($payload, 'benefits', data_get($payload, 'product.benefits'));
        $summary = data_get($payload, 'summary', data_get($payload, 'product.summary'));
        $benefits = is_array($benefitPayload)
            ? $benefitPayload
            : (is_string($summary) && $summary !== '' ? [$summary] : []);
        $seo = $projection->seo_json ?? [];
        $seo['meta_title'] ??= $projection->title;
        $seo['meta_description'] ??= data_get($payload, 'product.short_description', data_get($payload, 'product.description'));
        $seo['canonical_url'] ??= $urls->product($site, $locale, $projection);

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
            'categoryUrl' => is_string(data_get($payload, 'category.slug'))
                ? $urls->category($site, $locale, data_get($payload, 'category.slug'))
                : null,
            'specSections' => is_array(data_get($payload, 'spec_sections')) ? data_get($payload, 'spec_sections') : [],
            'benefits' => $benefits,
            'rating' => is_array(data_get($payload, 'rating')) ? data_get($payload, 'rating') : null,
            'media' => $projection->media_json ?? [],
            'seo' => $seo,
        ]);
    }
}

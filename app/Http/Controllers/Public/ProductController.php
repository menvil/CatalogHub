<?php

namespace App\Http\Controllers\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\PublicSite\LocalizedUrlResolver;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\SiteProductProjection;
use App\Services\Content\RelatedContentResolver;
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
        RelatedContentResolver $relatedContent,
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
        $projectionSeo = $projection->seo_json;
        $seo = is_array($projectionSeo) ? $projectionSeo : [];
        $seo = array_replace([
            'meta_title' => $projection->title,
            'meta_description' => data_get($payload, 'product.short_description', data_get($payload, 'product.description')),
            'canonical_url' => $urls->product($site, $locale, $projection),
        ], array_filter($seo, fn (mixed $value): bool => $value !== null));
        $category = is_array(data_get($payload, 'category')) ? data_get($payload, 'category') : null;
        $categoryUrl = is_string(data_get($payload, 'category.slug'))
            ? $urls->category($site, $locale, data_get($payload, 'category.slug'))
            : null;
        $breadcrumbs = [['label' => 'Home', 'url' => $urls->home($site, $locale)]];
        if ($category !== null) {
            $breadcrumbs[] = [
                'label' => $category['label'] ?? $category['name'] ?? 'Category',
                'url' => $categoryUrl,
            ];
        }
        $breadcrumbs[] = ['label' => $projection->title, 'url' => null];
        $enabledFeatures = $site->features()
            ->where('is_enabled', true)
            ->pluck('feature_key');
        $reviewsEnabled = $enabledFeatures->contains('reviews');
        $leadsEnabled = $enabledFeatures->contains('leads');
        $reviews = $reviewsEnabled
            ? Review::query()
                ->visiblePublicly()
                ->forSite($site)
                ->where('central_product_id', $projection->central_product_id)
                ->latest('approved_at')
                ->latest('id')
                ->limit(50)
                ->get()
            : collect();

        return view($layouts->resolve($site, 'product'), [
            'site' => $site,
            'locale' => $locale,
            'product' => [
                ...(is_array($productPayload) ? $productPayload : []),
                'title' => $projection->title,
                'slug' => $projection->slug,
            ],
            'brand' => is_array(data_get($payload, 'brand')) ? data_get($payload, 'brand') : null,
            'category' => $category,
            'categoryUrl' => $categoryUrl,
            'specSections' => is_array(data_get($payload, 'spec_sections')) ? data_get($payload, 'spec_sections') : [],
            'benefits' => $benefits,
            'rating' => is_array(data_get($payload, 'rating')) ? data_get($payload, 'rating') : null,
            'media' => $projection->media_json ?? [],
            'seo' => $seo,
            'breadcrumbs' => $breadcrumbs,
            'centralProductId' => (int) $projection->central_product_id,
            'reviewsEnabled' => $reviewsEnabled,
            'reviews' => $reviews,
            'leadsEnabled' => $leadsEnabled,
            'relatedContent' => $relatedContent->resolveForProduct(
                site: $site,
                locale: $locale,
                productId: (int) $projection->central_product_id,
                categoryId: $this->canonicalId(data_get($payload, 'category.id')),
                brandId: $this->canonicalId(data_get($payload, 'brand.id')),
            ),
        ]);
    }

    private function canonicalId(mixed $value): ?int
    {
        if (is_int($value) && $value > 0) {
            return $value;
        }

        if (is_string($value) && ctype_digit($value) && (int) $value > 0) {
            return (int) $value;
        }

        return null;
    }
}

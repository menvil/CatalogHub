<?php

namespace App\Http\Controllers\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use App\Models\SiteCategoryProjection;
use App\Models\SiteProductProjection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class ProductListingController extends Controller
{
    public function __invoke(
        Request $request,
        string $locale,
        string $slug,
        SiteContextResolver $sites,
        ThemeLayoutResolver $layouts,
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $category = SiteCategoryProjection::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where('slug', $slug)
            ->where('status', ProjectionStatus::Active)
            ->firstOrFail();
        $query = SiteProductProjection::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where('status', ProjectionStatus::Active)
            ->where('payload_json->category->id', $category->central_category_id);

        if ($request->string('sort')->toString() === 'title') {
            $query->orderBy('title')->orderBy('id');
        } else {
            $query->orderByDesc('built_at')->orderByDesc('id');
        }

        $perPage = max(1, min($request->integer('per_page', 12), 24));
        $products = $query->paginate($perPage)->withQueryString()->through(
            fn (SiteProductProjection $product): array => [
                'title' => $product->title,
                'slug' => $product->slug,
                'url' => "/{$locale}/products/{$product->slug}",
                'media' => $product->media_json ?? [],
                'summary' => $product->search_summary_json ?? [],
            ],
        );

        return view($layouts->resolve($site, 'listing'), [
            'site' => $site,
            'locale' => $locale,
            'category' => ['title' => $category->title, 'slug' => $category->slug],
            'products' => $products,
            'sort' => $request->string('sort')->toString() === 'title' ? 'title' : 'latest',
        ]);
    }
}

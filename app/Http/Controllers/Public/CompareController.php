<?php

namespace App\Http\Controllers\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\PublicSite\ComparisonViewModelBuilder;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSite\CompareProductsRequest;
use App\Models\SiteProductProjection;
use Illuminate\Contracts\View\View;

final class CompareController extends Controller
{
    public function __invoke(
        CompareProductsRequest $request,
        string $locale,
        SiteContextResolver $sites,
        ThemeLayoutResolver $layouts,
        ComparisonViewModelBuilder $comparison,
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $slugs = $request->comparisonData()->slugs;
        $available = SiteProductProjection::query()
            ->where('site_id', $site->id)
            ->where('locale', $locale)
            ->where('status', ProjectionStatus::Active)
            ->whereIn('slug', $slugs)
            ->get()
            ->keyBy('slug');
        $projections = collect($slugs)
            ->map(fn (string $slug): ?SiteProductProjection => $available->get($slug))
            ->filter(fn (?SiteProductProjection $projection): bool => $projection instanceof SiteProductProjection)
            ->values();

        return view($layouts->resolve($site, 'compare'), [
            'site' => $site,
            'locale' => $locale,
            'comparison' => $comparison->build($projections),
        ]);
    }
}

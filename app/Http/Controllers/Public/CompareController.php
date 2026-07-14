<?php

namespace App\Http\Controllers\Public;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\PublicSite\ComparisonViewModelBuilder;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use App\Models\SiteProductProjection;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class CompareController extends Controller
{
    public function __invoke(
        Request $request,
        string $locale,
        SiteContextResolver $sites,
        ThemeLayoutResolver $layouts,
        ComparisonViewModelBuilder $comparison,
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $rawSlugs = $request->query('products', []);
        $rawSlugs = is_string($rawSlugs) ? explode(',', $rawSlugs) : $rawSlugs;
        $slugs = is_array($rawSlugs)
            ? array_slice(array_values(array_unique(array_filter(array_map(
                fn (mixed $slug): string => is_string($slug) ? trim($slug) : '',
                $rawSlugs,
            )))), 0, 4)
            : [];
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

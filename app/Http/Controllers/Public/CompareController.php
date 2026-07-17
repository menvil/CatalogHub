<?php

namespace App\Http\Controllers\Public;

use App\Domains\PublicSite\ComparisonViewModelBuilder;
use App\Domains\PublicSite\SiteContextResolver;
use App\Domains\Themes\ThemeLayoutResolver;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicSite\CompareProductsRequest;
use App\Queries\PublicSite\PublicComparisonQuery;
use Illuminate\Contracts\View\View;

final class CompareController extends Controller
{
    public function __invoke(
        CompareProductsRequest $request,
        string $locale,
        SiteContextResolver $sites,
        ThemeLayoutResolver $layouts,
        ComparisonViewModelBuilder $comparison,
        PublicComparisonQuery $products,
    ): View {
        $site = $sites->resolve($request->getHost(), $locale);
        $slugs = $request->comparisonData()->slugs;
        $projections = $products->findActiveInOrder($site, $locale, $slugs);

        return view($layouts->resolve($site, 'compare'), [
            'site' => $site,
            'locale' => $locale,
            'comparison' => $comparison->build($projections),
        ]);
    }
}

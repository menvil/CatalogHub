<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\CentralCategorySlugImpactRequest;
use App\Models\CentralCatalog\CentralCategory;
use App\Queries\CentralCatalog\CategorySlugImpactQuery;
use Illuminate\Contracts\View\View;

/** Read-only CA-018 routing-impact fragment for a proposed canonical slug. */
final class CentralCategorySlugImpactController extends Controller
{
    public function __invoke(CentralCategorySlugImpactRequest $request, CentralCategory $category, CategorySlugImpactQuery $impacts): View
    {
        $proposed = $request->proposedSlug();

        return view('central-admin.categories._slug-impact', [
            'impact' => $proposed === $category->slug ? null : $impacts->assess($category, $category->slug, $proposed),
        ]);
    }
}

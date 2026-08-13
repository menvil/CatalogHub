<?php

namespace App\Http\Controllers\CentralAdmin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\Catalog\ListCentralBrandsRequest;
use App\Queries\CentralCatalog\CentralBrandListQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class CentralBrandsController extends Controller
{
    public function __invoke(
        ListCentralBrandsRequest $request,
        CentralBrandListQuery $query,
    ): View {
        Gate::authorize('catalog.products.manage');

        return view('central-admin.brands.index', [
            'brands' => $query
                ->paginate($request->filters(), $request->perPage())
                ->withQueryString(),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Enums\CentralBrandStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\CentralBrandListRequest;
use App\Queries\CentralCatalog\CentralBrandListQuery;
use Illuminate\Contracts\View\View;

final class CentralBrandListController extends Controller
{
    public function __invoke(CentralBrandListRequest $request, CentralBrandListQuery $query): View
    {
        $filters = $request->filters();

        return view('central-admin.brands.index', [
            'brands' => $query->paginate($filters),
            'filters' => $filters,
            'statusOptions' => CentralBrandStatus::options(),
        ]);
    }
}

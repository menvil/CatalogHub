<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Enums\CentralBrandStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\CentralBrandListRequest;
use App\Queries\CentralCatalog\CentralBrandListReadModelQuery;
use App\Services\Geography\CountryOptionProvider;
use Illuminate\Contracts\View\View;

final class CentralBrandListController extends Controller
{
    public function __invoke(
        CentralBrandListRequest $request,
        CentralBrandListReadModelQuery $query,
        CountryOptionProvider $countries,
    ): View {
        $filters = $request->filters();

        $list = $query->paginate($filters);

        return view('central-admin.brands.index', [
            'list' => $list,
            'brands' => $list->brands,
            'filters' => $filters,
            'statusOptions' => CentralBrandStatus::options(),
            'countryOptions' => $countries->options($filters->countryId, app()->getLocale()),
        ]);
    }
}

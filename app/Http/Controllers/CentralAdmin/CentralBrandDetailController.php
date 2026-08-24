<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Http\Controllers\Controller;
use App\Models\CentralCatalog\CentralBrand;
use App\Queries\CentralCatalog\CentralBrandDetailQuery;
use Illuminate\Contracts\View\View;

final class CentralBrandDetailController extends Controller
{
    public function __invoke(CentralBrand $brand, CentralBrandDetailQuery $query): View
    {
        return view('central-admin.brands.show', [
            'brand' => $query->loadUsage($brand),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Http\Controllers\Controller;
use App\Models\CentralCatalog\CentralBrand;
use App\Queries\CentralCatalog\CentralBrandDetailQuery;
use App\Queries\CentralCatalog\CentralBrandMediaQuery;
use App\Services\Geography\CountryNameResolver;
use App\Services\Media\BrandLogoPresenter;
use Illuminate\Contracts\View\View;

final class CentralBrandDetailController extends Controller
{
    public function __invoke(CentralBrand $brand, CentralBrandDetailQuery $query, CentralBrandMediaQuery $media, BrandLogoPresenter $logos, CountryNameResolver $countryNames): View
    {
        $brand = $query->loadUsage($brand);

        return view('central-admin.brands.show', [
            'brand' => $brand,
            'logo' => $logos->forDetail($media->logoFor($brand)),
            'countryName' => $brand->country === null
                ? null
                : $countryNames->nameFor($brand->country, app()->getLocale()),
        ]);
    }
}

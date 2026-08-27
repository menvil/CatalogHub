<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Http\Controllers\Controller;
use App\Models\CentralCatalog\CentralBrand;
use App\Queries\CentralCatalog\CentralBrandCategoryCoverageQuery;
use App\Queries\CentralCatalog\CentralBrandDetailQuery;
use App\Queries\CentralCatalog\CentralBrandQualityQuery;
use App\Queries\Imports\CentralBrandExternalIdentityQuery;
use App\Services\Geography\CountryNameResolver;
use Illuminate\Contracts\View\View;

final class CentralBrandDetailController extends Controller
{
    public function __invoke(
        CentralBrand $brand,
        CentralBrandDetailQuery $query,
        CentralBrandCategoryCoverageQuery $coverage,
        CentralBrandQualityQuery $quality,
        CountryNameResolver $countryNames,
        CentralBrandExternalIdentityQuery $externalIdentities,
    ): View {
        $brand = $query->loadUsage($brand);
        $brand = $externalIdentities->loadForBrand($brand);
        $qualityData = $quality->forBrand($brand);

        return view('central-admin.brands.show', [
            'brand' => $brand,
            'logo' => $qualityData->logo,
            'quality' => $qualityData->summary,
            'categoryCoverage' => $coverage->forBrand($brand),
            'activeImportSources' => $externalIdentities->activeSources(),
            'countryName' => $brand->country === null
                ? null
                : $countryNames->nameFor($brand->country, app()->getLocale()),
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Http\Controllers\Controller;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAssignment;
use App\Queries\CentralCatalog\CentralBrandDetailQuery;
use App\Services\Media\MediaResolver;
use App\Services\Media\MediaUrlGenerator;
use Illuminate\Contracts\View\View;

final class CentralBrandDetailController extends Controller
{
    public function __invoke(CentralBrand $brand, CentralBrandDetailQuery $query, MediaResolver $media, MediaUrlGenerator $urls): View
    {
        return view('central-admin.brands.show', [
            'brand' => $query->loadUsage($brand),
            'logoAsset' => $media->resolve(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id, MediaAssignment::ROLE_BRAND_LOGO),
            'mediaUrls' => $urls,
        ]);
    }
}

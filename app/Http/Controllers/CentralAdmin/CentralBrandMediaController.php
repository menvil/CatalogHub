<?php

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\CentralCatalog\RemoveCentralBrandLogoAction;
use App\Actions\CentralCatalog\UploadCentralBrandLogoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\UploadCentralBrandLogoRequest;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAssignment;
use App\Services\Media\ImageIngestException;
use App\Services\Media\MediaResolver;
use App\Services\Media\MediaUrlGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class CentralBrandMediaController extends Controller
{
    public function show(CentralBrand $brand, MediaResolver $resolver, MediaUrlGenerator $urls): View
    {
        $asset = $resolver->resolve(MediaAssignment::ENTITY_TYPE_CENTRAL_BRAND, $brand->id, MediaAssignment::ROLE_BRAND_LOGO);

        return view('central-admin.brands.media', compact('brand', 'asset', 'urls'));
    }

    public function storeLogo(UploadCentralBrandLogoRequest $request, CentralBrand $brand, UploadCentralBrandLogoAction $action): RedirectResponse
    {
        try {
            $action->execute($brand, $request->logo());
        } catch (ImageIngestException $e) {
            return back()->withErrors(['logo' => $e->getMessage()])->withInput();
        }

        return redirect()->route('central.brands.media', $brand)->with('status', 'Brand logo updated.');
    }

    public function destroyLogo(CentralBrand $brand, RemoveCentralBrandLogoAction $action): RedirectResponse
    {
        $action->execute($brand);

        return redirect()->route('central.brands.media', $brand)->with('status', 'Brand logo removed.');
    }
}

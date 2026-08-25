<?php

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\CentralCatalog\RemoveCentralBrandLogoAction;
use App\Actions\CentralCatalog\UploadCentralBrandLogoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\UploadCentralBrandLogoRequest;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use App\Queries\CentralCatalog\CentralBrandMediaQuery;
use App\Services\Media\BrandLogoPresenter;
use App\Services\Media\ImageIngestException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class CentralBrandMediaController extends Controller
{
    public function show(CentralBrand $brand, CentralBrandMediaQuery $query, BrandLogoPresenter $logos): View
    {
        $asset = $query->logoFor($brand);

        return view('central-admin.brands.media', ['brand' => $brand, 'asset' => $asset, 'logo' => $logos->forMedia($asset)]);
    }

    public function storeLogo(UploadCentralBrandLogoRequest $request, CentralBrand $brand, UploadCentralBrandLogoAction $action): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);

        try {
            $action($actor, $brand, $request->logo());
        } catch (ImageIngestException $e) {
            return back()->withErrors(['logo' => $e->getMessage()])->withInput();
        }

        return redirect()->route('central.brands.media', $brand)->with('status', 'Brand logo updated.');
    }

    public function destroyLogo(Request $request, CentralBrand $brand, RemoveCentralBrandLogoAction $action): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);
        $action($actor, $brand);

        return redirect()->route('central.brands.media', $brand)->with('status', 'Brand logo removed.');
    }
}

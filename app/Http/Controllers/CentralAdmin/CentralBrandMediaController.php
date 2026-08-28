<?php

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\CentralCatalog\AssignExistingCentralBrandLogoAction;
use App\Actions\CentralCatalog\RemoveCentralBrandLogoAction;
use App\Actions\CentralCatalog\UploadCentralBrandLogoAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\AssignExistingCentralBrandLogoRequest;
use App\Http\Requests\CentralAdmin\ListCentralBrandMediaAssetsRequest;
use App\Http\Requests\CentralAdmin\UploadCentralBrandLogoRequest;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\MediaAsset;
use App\Models\User;
use App\Queries\CentralCatalog\CentralBrandMediaQuery;
use App\Queries\Media\MediaLibraryQuery;
use App\Services\Media\BrandLogoPresenter;
use App\Services\Media\ImageIngestException;
use App\Services\Media\MediaUploadException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

final class CentralBrandMediaController extends Controller
{
    public function show(
        ListCentralBrandMediaAssetsRequest $request,
        CentralBrand $brand,
        CentralBrandMediaQuery $query,
        BrandLogoPresenter $logos,
        MediaLibraryQuery $library,
    ): View {
        $assignment = $query->primaryLogoAssignmentFor($brand);
        $asset = $assignment?->asset;
        $availableAssets = null;
        $availableLogos = collect();

        if (Gate::allows('media.manage')) {
            $availableAssets = $library->paginateCompatibleImages(
                $request->assetSearch(),
                page: $request->assetPage(),
            )->withQueryString();
            $availableLogos = $availableAssets->getCollection()->mapWithKeys(
                static fn (MediaAsset $candidate): array => [(int) $candidate->getKey() => $logos->forMedia($candidate)],
            );
        }

        return view('central-admin.brands.media', [
            'brand' => $brand,
            'assignment' => $assignment,
            'asset' => $asset,
            'logo' => $logos->forMedia($asset),
            'variants' => $logos->variantsForMedia($asset),
            'availableAssets' => $availableAssets,
            'availableLogos' => $availableLogos,
            'assetSearch' => $request->assetSearch(),
        ]);
    }

    public function storeLogo(UploadCentralBrandLogoRequest $request, CentralBrand $brand, UploadCentralBrandLogoAction $action): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);

        try {
            $result = $action($actor, $brand, $request->logo());
        } catch (ImageIngestException $e) {
            return back()->withErrors(['logo' => $e->getMessage()])->withInput();
        } catch (MediaUploadException) {
            return back()->withErrors([
                'logo' => 'The logo could not be stored. The existing assignment was not changed.',
            ])->withInput();
        }

        return redirect()->route('central.brands.media', $brand)->with(
            'success',
            $result->changed ? 'Brand logo updated.' : 'This media asset is already the Brand logo.',
        );
    }

    public function assignLogo(
        AssignExistingCentralBrandLogoRequest $request,
        CentralBrand $brand,
        AssignExistingCentralBrandLogoAction $action,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);
        $result = $action->execute($actor, $brand, $request->mediaAssetId());

        return redirect()->route('central.brands.media', $brand)->with(
            'success',
            $result->changed
                ? 'Existing media asset assigned as the Brand logo.'
                : 'This media asset is already the Brand logo.',
        );
    }

    public function destroyLogo(Request $request, CentralBrand $brand, RemoveCentralBrandLogoAction $action): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);
        $removed = $action($actor, $brand);

        return redirect()->route('central.brands.media', $brand)->with(
            $removed ? 'success' : 'warning',
            $removed ? 'Brand logo assignment removed.' : 'No canonical Brand logo assignment exists.',
        );
    }
}

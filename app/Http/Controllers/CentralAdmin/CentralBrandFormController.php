<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\CentralCatalog\CreateCentralBrandAction;
use App\Actions\CentralCatalog\UpdateCentralBrandAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\CentralBrandFormRequest;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use App\Queries\CentralCatalog\CentralBrandMediaQuery;
use App\Services\Geography\CountryOptionProvider;
use App\Services\Media\BrandLogoPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class CentralBrandFormController extends Controller
{
    public function create(CountryOptionProvider $countries): View
    {
        return view('central-admin.brands.create', [
            'countryOptions' => $countries->options(null, app()->getLocale()),
        ]);
    }

    public function store(CentralBrandFormRequest $request, CreateCentralBrandAction $createBrand): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);
        $createBrand->handle($actor, $request->brandInput());

        return redirect()
            ->route('central.brands.index')
            ->with('success', 'Brand created.');
    }

    public function edit(
        CentralBrand $brand,
        CountryOptionProvider $countries,
        CentralBrandMediaQuery $media,
        BrandLogoPresenter $logos,
    ): View {
        return view('central-admin.brands.edit', [
            'brand' => $brand,
            'countryOptions' => $countries->options($brand->country_id, app()->getLocale()),
            'logo' => $logos->forDetail($media->logoFor($brand)),
        ]);
    }

    public function update(
        CentralBrandFormRequest $request,
        CentralBrand $brand,
        UpdateCentralBrandAction $updateBrand,
    ): RedirectResponse {
        $actor = $request->user();
        assert($actor instanceof User);
        $updateBrand->handle($actor, $brand, $request->brandInput());

        return redirect()
            ->route('central.brands.edit', $brand)
            ->with('success', 'Brand updated.');
    }
}

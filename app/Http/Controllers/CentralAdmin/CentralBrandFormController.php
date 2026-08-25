<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\CentralCatalog\CreateCentralBrandAction;
use App\Actions\CentralCatalog\UpdateCentralBrandAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CentralAdmin\CentralBrandFormRequest;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

final class CentralBrandFormController extends Controller
{
    public function create(): View
    {
        return view('central-admin.brands.create');
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

    public function edit(CentralBrand $brand): View
    {
        return view('central-admin.brands.edit', ['brand' => $brand]);
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

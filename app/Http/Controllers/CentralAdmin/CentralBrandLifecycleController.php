<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\CentralCatalog\ActivateCentralBrandAction;
use App\Actions\CentralCatalog\ArchiveCentralBrandAction;
use App\Actions\CentralCatalog\RestoreCentralBrandAction;
use App\Enums\CentralBrandStatus;
use App\Http\Controllers\Controller;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;

final class CentralBrandLifecycleController extends Controller
{
    public function activate(CentralBrand $brand, ActivateCentralBrandAction $activateBrand): RedirectResponse
    {
        try {
            $activateBrand->handle($brand);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('central.brands.show', $brand)
                ->withErrors($exception->errors())
                ->with('lifecycle_error', $exception->validator->errors()->first('status'));
        }

        return redirect()
            ->route('central.brands.show', $brand)
            ->with('success', 'Brand activated.');
    }

    public function archive(CentralBrand $brand, ArchiveCentralBrandAction $archiveBrand): RedirectResponse
    {
        $archiveBrand->handle($brand);

        return redirect()
            ->route('central.brands.show', $brand)
            ->with('success', 'Brand archived.');
    }

    public function restore(CentralBrand $brand, RestoreCentralBrandAction $restoreBrand): RedirectResponse
    {
        try {
            $restoredBrand = $restoreBrand->handle($brand);

            if ($restoredBrand->status !== CentralBrandStatus::Draft) {
                throw ValidationException::withMessages([
                    'status' => 'Only archived brands can be restored.',
                ]);
            }
        } catch (ValidationException $exception) {
            return redirect()
                ->route('central.brands.show', $brand)
                ->withErrors($exception->errors())
                ->with('lifecycle_error', $exception->validator->errors()->first('status'));
        }

        return redirect()
            ->route('central.brands.show', $brand)
            ->with('success', 'Brand restored to Draft.');
    }
}

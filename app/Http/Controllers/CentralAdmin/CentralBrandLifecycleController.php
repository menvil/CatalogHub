<?php

declare(strict_types=1);

namespace App\Http\Controllers\CentralAdmin;

use App\Actions\CentralCatalog\ActivateCentralBrandAction;
use App\Actions\CentralCatalog\ArchiveCentralBrandAction;
use App\Actions\CentralCatalog\RestoreCentralBrandAction;
use App\Enums\CentralBrandStatus;
use App\Http\Controllers\Controller;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class CentralBrandLifecycleController extends Controller
{
    public function activate(Request $request, CentralBrand $brand, ActivateCentralBrandAction $activateBrand): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);

        try {
            $activateBrand->handle($actor, $brand);
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

    public function archive(Request $request, CentralBrand $brand, ArchiveCentralBrandAction $archiveBrand): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);
        $archiveBrand->handle($actor, $brand);

        return redirect()
            ->route('central.brands.show', $brand)
            ->with('success', 'Brand archived.');
    }

    public function restore(Request $request, CentralBrand $brand, RestoreCentralBrandAction $restoreBrand): RedirectResponse
    {
        $actor = $request->user();
        assert($actor instanceof User);

        try {
            $restoredBrand = $restoreBrand->handle($actor, $brand);

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

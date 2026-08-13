<?php

namespace App\Actions\CentralCatalog;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Validation\ValidationException;

final class ActivateCentralBrandAction
{
    public function handle(CentralBrand $brand): CentralBrand
    {
        $brand->refresh();

        if ($brand->status === CentralBrandStatus::Archived) {
            throw ValidationException::withMessages([
                'status' => 'Archived brands must be restored before they can be activated.',
            ]);
        }

        if ($brand->status === CentralBrandStatus::Active) {
            return $brand;
        }

        $brand->forceFill(['status' => CentralBrandStatus::Active])->saveOrFail();

        return $brand->refresh();
    }
}

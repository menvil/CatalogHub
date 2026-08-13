<?php

namespace App\Actions\CentralCatalog;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ActivateCentralBrandAction
{
    public function handle(CentralBrand $brand): CentralBrand
    {
        return DB::transaction(function () use ($brand): CentralBrand {
            $lockedBrand = CentralBrand::query()
                ->lockForUpdate()
                ->findOrFail($brand->getKey());

            if ($lockedBrand->status === CentralBrandStatus::Archived) {
                throw ValidationException::withMessages([
                    'status' => 'Archived brands must be restored before they can be activated.',
                ]);
            }

            if ($lockedBrand->status === CentralBrandStatus::Active) {
                return $lockedBrand;
            }

            $lockedBrand->forceFill(['status' => CentralBrandStatus::Active])->saveOrFail();

            return $lockedBrand->refresh();
        });
    }
}

<?php

namespace App\Actions\CentralCatalog;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use Illuminate\Support\Facades\DB;

final class RestoreCentralBrandAction
{
    public function handle(CentralBrand $brand): CentralBrand
    {
        return DB::transaction(function () use ($brand): CentralBrand {
            $lockedBrand = CentralBrand::query()
                ->lockForUpdate()
                ->findOrFail($brand->getKey());

            if ($lockedBrand->status !== CentralBrandStatus::Archived) {
                return $lockedBrand;
            }

            $lockedBrand->forceFill(['status' => CentralBrandStatus::Draft])->saveOrFail();

            return $lockedBrand->refresh();
        });
    }
}

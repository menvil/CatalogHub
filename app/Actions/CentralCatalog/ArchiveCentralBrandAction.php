<?php

namespace App\Actions\CentralCatalog;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;

final class ArchiveCentralBrandAction
{
    public function handle(CentralBrand $brand): CentralBrand
    {
        $brand->refresh();

        if ($brand->status === CentralBrandStatus::Archived) {
            return $brand;
        }

        $brand->forceFill(['status' => CentralBrandStatus::Archived])->saveOrFail();

        return $brand->refresh();
    }
}

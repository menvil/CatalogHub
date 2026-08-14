<?php

namespace App\Actions\CentralCatalog;

use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;

final class RestoreCentralBrandAction
{
    public function handle(CentralBrand $brand): CentralBrand
    {
        $brand->refresh();

        if ($brand->status !== CentralBrandStatus::Archived) {
            return $brand;
        }

        $brand->forceFill(['status' => CentralBrandStatus::Draft])->saveOrFail();

        return $brand->refresh();
    }
}

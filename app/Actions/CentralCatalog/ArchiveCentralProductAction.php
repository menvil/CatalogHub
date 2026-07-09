<?php

namespace App\Actions\CentralCatalog;

use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralProduct;

final class ArchiveCentralProductAction
{
    public function handle(CentralProduct $product): CentralProduct
    {
        $product->forceFill([
            'status' => CentralProductStatus::Archived,
        ])->save();

        return $product->refresh();
    }
}

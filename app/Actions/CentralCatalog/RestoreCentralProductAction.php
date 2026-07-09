<?php

namespace App\Actions\CentralCatalog;

use App\Enums\CentralProductStatus;
use App\Models\CentralCatalog\CentralProduct;

final class RestoreCentralProductAction
{
    public function handle(CentralProduct $product): CentralProduct
    {
        if ($product->status !== CentralProductStatus::Archived) {
            return $product->refresh();
        }

        $product->forceFill([
            'status' => CentralProductStatus::Draft,
        ])->save();

        return $product->refresh();
    }
}

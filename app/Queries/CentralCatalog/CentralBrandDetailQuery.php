<?php

declare(strict_types=1);

namespace App\Queries\CentralCatalog;

use App\Models\CentralCatalog\CentralBrand;

final class CentralBrandDetailQuery
{
    public function loadUsage(CentralBrand $brand): CentralBrand
    {
        return $brand
            ->load(['country.translations', 'tags', 'ownership.organization'])
            ->loadCount('products');
    }
}

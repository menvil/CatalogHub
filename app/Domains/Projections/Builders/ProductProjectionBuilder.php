<?php

namespace App\Domains\Projections\Builders;

use App\Domains\Projections\DTO\ProductProjectionData;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use LogicException;

final class ProductProjectionBuilder
{
    public function build(Site $site, CentralProduct $product, string $locale): ProductProjectionData
    {
        throw new LogicException('Product projection building is not implemented yet.');
    }
}

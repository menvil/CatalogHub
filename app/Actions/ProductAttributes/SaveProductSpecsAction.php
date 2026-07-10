<?php

namespace App\Actions\ProductAttributes;

use App\Models\CentralCatalog\CentralProduct;
use App\Services\ProductAttributes\ProductAttributeValueValidator;

final class SaveProductSpecsAction
{
    public function __construct(
        private readonly ProductAttributeValueValidator $validator,
    ) {}

    /**
     * @param  array<int|string, array<string, mixed>>  $payload
     * @return array<int, array<string, mixed>>
     */
    public function handle(CentralProduct $product, array $payload): array
    {
        return $this->validator->validate($product, $payload);
    }
}

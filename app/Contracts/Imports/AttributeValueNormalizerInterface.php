<?php

namespace App\Contracts\Imports;

use App\Data\Imports\NormalizedAttributeValueData;
use App\Models\CentralCatalog\AttributeDefinition;

interface AttributeValueNormalizerInterface
{
    public function supports(AttributeDefinition $definition): bool;

    public function normalize(
        AttributeDefinition $definition,
        mixed $rawValue,
    ): NormalizedAttributeValueData;
}

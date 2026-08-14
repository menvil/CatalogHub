<?php

namespace App\Queries\CentralCatalog;

use App\Models\CentralCatalog\CentralBrand;
use App\Support\Normalization\BrandInputNormalizer;

final class DuplicateCentralBrandNameQuery
{
    public function exists(string $normalizedName, ?CentralBrand $except = null): bool
    {
        $identity = BrandInputNormalizer::nameIdentity($normalizedName);
        $query = CentralBrand::query()
            ->where('normalized_name_hash', BrandInputNormalizer::nameIdentityHash($normalizedName));

        if ($except !== null) {
            $query->where($except->getKeyName(), '!=', $except->getKey());
        }

        return $query
            ->pluck('normalized_name')
            ->contains(static fn (mixed $candidate): bool => is_string($candidate) && hash_equals($identity, $candidate));
    }
}

<?php

declare(strict_types=1);

namespace App\Services\Imports;

use App\Exceptions\Imports\ExternalIdentityIntegrityException;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Imports\CentralBrandExternalIdentity;
use App\Models\Imports\ImportSource;
use App\Support\Imports\ExternalIdentityNormalizer;

final class CentralBrandExternalIdentityResolver
{
    public function findIdentity(ImportSource $source, string $externalId): ?CentralBrandExternalIdentity
    {
        $normalized = ExternalIdentityNormalizer::externalId($externalId);
        $identity = CentralBrandExternalIdentity::query()
            ->where('import_source_id', $source->getKey())
            ->where('external_id_hash', ExternalIdentityNormalizer::hash($normalized))
            ->first();

        if (! $identity instanceof CentralBrandExternalIdentity) {
            return null;
        }

        return $this->assertExact($identity, $normalized);
    }

    public function findBrand(ImportSource $source, string $externalId): ?CentralBrand
    {
        return $this->findIdentity($source, $externalId)?->brand()->first();
    }

    public function assertExact(CentralBrandExternalIdentity $identity, string $normalizedExternalId): CentralBrandExternalIdentity
    {
        if (! hash_equals((string) $identity->external_id, $normalizedExternalId)) {
            throw new ExternalIdentityIntegrityException('An external identity SHA-256 collision was detected.');
        }

        return $identity;
    }
}

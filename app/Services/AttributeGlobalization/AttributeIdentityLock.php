<?php

namespace App\Services\AttributeGlobalization;

use App\Models\CentralCatalog\AttributeIdentityScope;
use App\Services\SchemaCutoverV2\IdentityMutex;
use Illuminate\Validation\ValidationException;

/** Shared membership/identity mutex, always before Category and definition locks. */
final class AttributeIdentityLock
{
    public function acquire(): void
    {
        (new IdentityMutex)->acquire();
    }

    /** Holds the MariaDB mutex across DDL transaction boundaries. */
    public function deployment(\Closure $operation): void
    {
        (new IdentityMutex)->deployment($operation);
    }

    public function releaseAfterTransaction(): void
    {
        (new IdentityMutex)->releaseAfterTransaction();
    }

    public function acquireConsumerRead(): void
    {
        $this->acquire();
        $version = (int) AttributeIdentityScope::query()->whereKey(1)->value('consumer_version');
        if ($version !== SchemaConsumerVersion::CURRENT && ! ($version === 0 && (new IdentityMutex)->deploymentHeld())) {
            throw ValidationException::withMessages(['attribute_identity_version' => 'Target identity reads are paused until cutover/rebuild completes.']);
        }
    }

    public function acquireTarget(): void
    {
        $this->acquire();
        if ((int) AttributeIdentityScope::query()->whereKey(1)->value('consumer_version') !== SchemaConsumerVersion::CURRENT) {
            throw ValidationException::withMessages(['attribute_identity_version' => 'Schema/spec/import writes are paused until the v2 consumer cutover finishes.']);
        }
    }

    public function recordTargetWrite(): void
    {
        AttributeIdentityScope::query()->toBase()->where('id', 1)->increment('write_epoch');
    }
}

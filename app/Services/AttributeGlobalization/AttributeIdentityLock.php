<?php

namespace App\Services\AttributeGlobalization;

use App\Models\CentralCatalog\AttributeIdentityScope;
use App\Queries\Attributes\AttributeIdentityConnectionLockQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Shared membership/identity mutex, always before Category and definition locks. */
final class AttributeIdentityLock
{
    private int $deploymentHolds = 0;

    public function acquire(): void
    {
        app(AttributeIdentityConnectionLockQuery::class)->acquire();
        AttributeIdentityScope::query()->toBase()->where('id', 1)->increment('write_epoch', 0);
        AttributeIdentityScope::query()->toBase()->where('id', 1)->lockForUpdate()->firstOrFail();
    }

    /** Holds the MariaDB mutex across DDL transaction boundaries. */
    public function deployment(\Closure $operation): void
    {
        app(AttributeIdentityConnectionLockQuery::class)->acquire();
        $this->deploymentHolds++;
        try {
            $operation();
        } finally {
            $this->deploymentHolds--;
            $this->releaseAfterTransaction();
        }
    }

    public function releaseAfterTransaction(): void
    {
        if ($this->deploymentHolds === 0 && DB::transactionLevel() === 0) {
            app(AttributeIdentityConnectionLockQuery::class)->release();
        }
    }

    public function acquireConsumerRead(): void
    {
        $this->acquire();
        $version = (int) AttributeIdentityScope::query()->whereKey(1)->value('consumer_version');
        if ($version !== SchemaConsumerVersion::CURRENT && ! ($version === 0 && $this->deploymentHolds > 0)) {
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

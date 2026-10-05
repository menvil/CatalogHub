<?php

namespace App\Services\AttributeGlobalization;

use App\Models\CentralCatalog\AttributeIdentityScope;

/** Shared membership/identity mutex, always before Category and definition locks. */
final class AttributeIdentityLock
{
    public function acquire(): void
    {
        AttributeIdentityScope::query()->toBase()->where('id', 1)->increment('write_epoch', 0);
        AttributeIdentityScope::query()->toBase()->where('id', 1)->lockForUpdate()->firstOrFail();
    }

    public function recordTargetWrite(): void
    {
        AttributeIdentityScope::query()->toBase()->where('id', 1)->increment('write_epoch');
    }
}

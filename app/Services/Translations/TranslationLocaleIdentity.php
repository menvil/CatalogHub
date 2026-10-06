<?php

namespace App\Services\Translations;

use App\Services\AttributeGlobalization\AttributeIdentityLock;
use Illuminate\Database\Eloquent\Model;

final class TranslationLocaleIdentity
{
    /** @template T of Model
     * @param  T  $owner
     * @return T
     */
    public function lockOwner(Model $owner): Model
    {
        app(AttributeIdentityLock::class)->acquire();

        return $owner::query()->whereKey($owner->getKey())->lockForUpdate()->firstOrFail();
    }
}

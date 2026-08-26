<?php

declare(strict_types=1);

namespace App\Actions\Imports;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\Imports\CentralBrandExternalIdentity;
use App\Models\Imports\ImportSource;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

final readonly class RemoveCentralBrandExternalIdentityAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function handle(
        User $actor,
        CentralBrand $brand,
        CentralBrandExternalIdentity $identity,
    ): CentralBrand {
        return DB::transaction(function () use ($actor, $brand, $identity): CentralBrand {
            $lockedBrand = CentralBrand::query()->lockForUpdate()->findOrFail($brand->getKey());
            $lockedIdentity = CentralBrandExternalIdentity::query()->lockForUpdate()->findOrFail($identity->getKey());

            if ((int) $lockedIdentity->central_brand_id !== (int) $lockedBrand->getKey()) {
                throw (new ModelNotFoundException)->setModel(CentralBrandExternalIdentity::class, [$identity->getKey()]);
            }

            $source = ImportSource::query()->findOrFail($lockedIdentity->import_source_id);
            $snapshot = [
                'source_code' => (string) $source->code,
                'external_id' => (string) $lockedIdentity->external_id,
                'external_url' => $lockedIdentity->external_url === null ? null : (string) $lockedIdentity->external_url,
            ];

            $lockedIdentity->deleteOrFail();
            $this->audit->record(
                AuditAction::CatalogBrandExternalIdentityUnlinked,
                AuditContext::Central,
                $actor,
                $lockedBrand,
                null,
                $snapshot,
                null,
            );

            return $lockedBrand;
        });
    }
}

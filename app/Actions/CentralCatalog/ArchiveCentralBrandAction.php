<?php

namespace App\Actions\CentralCatalog;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

final readonly class ArchiveCentralBrandAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function handle(User $actor, CentralBrand $brand): CentralBrand
    {
        return DB::transaction(function () use ($actor, $brand): CentralBrand {
            $lockedBrand = CentralBrand::query()
                ->lockForUpdate()
                ->findOrFail($brand->getKey());

            if ($lockedBrand->status === CentralBrandStatus::Archived) {
                return $lockedBrand;
            }

            $beforeStatus = $lockedBrand->status;
            $lockedBrand->forceFill(['status' => CentralBrandStatus::Archived])->saveOrFail();
            $this->audit->record(AuditAction::CatalogBrandArchived, AuditContext::Central, $actor, $lockedBrand, null, ['status' => $beforeStatus->value], ['status' => CentralBrandStatus::Archived->value]);

            return $lockedBrand->refresh();
        });
    }
}

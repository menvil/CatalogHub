<?php

namespace App\Actions\CentralCatalog;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;

final readonly class RestoreCentralBrandAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function handle(User $actor, CentralBrand $brand): CentralBrand
    {
        return DB::transaction(function () use ($actor, $brand): CentralBrand {
            $lockedBrand = CentralBrand::query()
                ->lockForUpdate()
                ->findOrFail($brand->getKey());

            if ($lockedBrand->status !== CentralBrandStatus::Archived) {
                return $lockedBrand;
            }

            $lockedBrand->forceFill(['status' => CentralBrandStatus::Draft])->saveOrFail();
            $this->audit->record(AuditAction::CatalogBrandRestored, AuditContext::Central, $actor, $lockedBrand, null, ['status' => CentralBrandStatus::Archived->value], ['status' => CentralBrandStatus::Draft->value]);

            return $lockedBrand->refresh();
        });
    }
}

<?php

namespace App\Actions\CentralCatalog;

use App\Enums\AuditAction;
use App\Enums\AuditContext;
use App\Enums\CentralBrandStatus;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\User;
use App\Services\Audit\AuditRecorder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final readonly class ActivateCentralBrandAction
{
    public function __construct(private AuditRecorder $audit) {}

    public function handle(User $actor, CentralBrand $brand): CentralBrand
    {
        return DB::transaction(function () use ($actor, $brand): CentralBrand {
            $lockedBrand = CentralBrand::query()
                ->lockForUpdate()
                ->findOrFail($brand->getKey());

            if ($lockedBrand->status === CentralBrandStatus::Archived) {
                throw ValidationException::withMessages([
                    'status' => 'Archived brands must be restored before they can be activated.',
                ]);
            }

            if ($lockedBrand->status === CentralBrandStatus::Active) {
                return $lockedBrand;
            }

            $lockedBrand->forceFill(['status' => CentralBrandStatus::Active])->saveOrFail();
            $this->audit->record(AuditAction::CatalogBrandActivated, AuditContext::Central, $actor, $lockedBrand, null, ['status' => CentralBrandStatus::Draft->value], ['status' => CentralBrandStatus::Active->value]);

            return $lockedBrand->refresh();
        });
    }
}

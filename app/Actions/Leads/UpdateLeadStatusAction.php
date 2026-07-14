<?php

namespace App\Actions\Leads;

use App\Enums\LeadStatus;
use App\Exceptions\Leads\CannotUpdateLeadException;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateLeadStatusAction
{
    public function handle(User $user, Lead $lead, LeadStatus $status): Lead
    {
        return DB::transaction(function () use ($lead, $status, $user): Lead {
            $lockedLead = Lead::query()->lockForUpdate()->findOrFail($lead->getKey());
            $canManage = $user->hasCatalogHubPermission('leads.manage')
                && ($user->isSuperAdmin() || (int) $user->site_id === (int) $lockedLead->site_id);

            if (! $canManage) {
                throw CannotUpdateLeadException::because('You cannot update leads for this site.');
            }

            $lockedLead->forceFill(['status' => $status])->save();

            return $lockedLead->refresh();
        });
    }
}

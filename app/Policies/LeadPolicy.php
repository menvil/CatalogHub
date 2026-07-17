<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

final class LeadPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasCatalogHubPermission('leads.manage');
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->viewAny($user)
            && ($user->isSuperAdmin()
                || ($user->site_id !== null && (int) $user->site_id === (int) $lead->site_id));
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead);
    }
}

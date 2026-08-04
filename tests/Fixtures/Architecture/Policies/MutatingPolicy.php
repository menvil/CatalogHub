<?php

namespace App\Policies\ArchitectureFixtures;

use App\Models\Site;

final class MutatingPolicy
{
    public function update(Site $site): bool
    {
        return $site->update(['name' => 'Changed']);
    }
}

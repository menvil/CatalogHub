<?php

namespace App\Domains\Themes\Actions;

use App\Models\Site;
use App\Models\SiteHomeBlock;
use Illuminate\Support\Facades\DB;

final class ToggleSiteHomeBlockAction
{
    public function handle(Site $site, SiteHomeBlock $homeBlock): void
    {
        DB::transaction(function () use ($site, $homeBlock): void {
            $lockedSite = Site::query()->lockForUpdate()->findOrFail($site->getKey());
            $lockedBlock = $lockedSite->homeBlocks()->lockForUpdate()->findOrFail($homeBlock->getKey());
            $lockedBlock->update(['enabled' => ! $lockedBlock->enabled]);
        });
    }
}

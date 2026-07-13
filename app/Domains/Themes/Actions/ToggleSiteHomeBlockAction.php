<?php

namespace App\Domains\Themes\Actions;

use App\Domains\Themes\Services\BlockCompatibilityValidator;
use App\Models\Site;
use App\Models\SiteHomeBlock;
use Illuminate\Support\Facades\DB;

final class ToggleSiteHomeBlockAction
{
    public function __construct(private readonly BlockCompatibilityValidator $compatibility) {}

    public function handle(Site $site, SiteHomeBlock $homeBlock): void
    {
        DB::transaction(function () use ($site, $homeBlock): void {
            $lockedSite = Site::query()->lockForUpdate()->findOrFail($site->getKey());
            $lockedBlock = $lockedSite->homeBlocks()->lockForUpdate()->findOrFail($homeBlock->getKey());

            if (! $lockedBlock->enabled) {
                $this->compatibility->validate($lockedSite, $lockedBlock->block_code);
            }

            $lockedBlock->update(['enabled' => ! $lockedBlock->enabled]);
        });
    }
}

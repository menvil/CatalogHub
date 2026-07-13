<?php

namespace App\Domains\Themes\Actions;

use App\Domains\Themes\Services\BlockCompatibilityValidator;
use App\Domains\Themes\Services\BlockConfigValidator;
use App\Exceptions\Themes\CannotUseBlockException;
use App\Models\BlockDefinition;
use App\Models\Site;
use App\Models\SiteHomeBlock;
use Illuminate\Support\Facades\DB;

final class UpdateSiteHomeBlockConfigAction
{
    public function __construct(
        private readonly BlockCompatibilityValidator $compatibility,
        private readonly BlockConfigValidator $configValidator,
    ) {}

    /** @param array<string, mixed> $config */
    public function handle(Site $site, SiteHomeBlock $homeBlock, array $config): void
    {
        DB::transaction(function () use ($site, $homeBlock, $config): void {
            $lockedSite = Site::query()->lockForUpdate()->findOrFail($site->getKey());
            $lockedBlock = $lockedSite->homeBlocks()->with('definition')->findOrFail($homeBlock->getKey());
            $this->compatibility->validate($lockedSite, $lockedBlock->block_code);
            $definition = $lockedBlock->definition;
            if (! $definition instanceof BlockDefinition) {
                throw CannotUseBlockException::because("Block {$lockedBlock->block_code} is not registered.");
            }
            $this->configValidator->validate($definition, $config);
            $lockedBlock->update(['config_json' => $config]);
        });
    }
}

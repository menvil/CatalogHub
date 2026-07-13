<?php

namespace App\Domains\Themes\Actions;

use App\Domains\Themes\Services\BlockCompatibilityValidator;
use App\Domains\Themes\Services\BlockConfigValidator;
use App\Domains\Themes\Services\BlockRegistry;
use App\Exceptions\Themes\CannotUseBlockException;
use App\Models\BlockDefinition;
use App\Models\Site;
use App\Models\SiteHomeBlock;
use Illuminate\Support\Facades\DB;

final class AddSiteHomeBlockAction
{
    public function __construct(
        private readonly BlockCompatibilityValidator $compatibility,
        private readonly BlockConfigValidator $configValidator,
        private readonly BlockRegistry $blocks,
    ) {}

    /** @param array<string, mixed> $config */
    public function handle(Site $site, string $blockCode, array $config = []): SiteHomeBlock
    {
        return DB::transaction(function () use ($site, $blockCode, $config): SiteHomeBlock {
            $lockedSite = Site::query()->lockForUpdate()->findOrFail($site->getKey());
            $definition = $this->blocks->findByCode($blockCode);

            if (! $definition instanceof BlockDefinition) {
                throw CannotUseBlockException::because("Block {$blockCode} is not registered.");
            }

            $this->compatibility->validate($lockedSite, $definition->code);
            $this->configValidator->validate($definition, $config);
            $position = ((int) $lockedSite->homeBlocks()->max('position')) + 1;

            return $lockedSite->homeBlocks()->create([
                'block_code' => $blockCode,
                'position' => $position,
                'enabled' => true,
                'config_json' => $config,
            ]);
        });
    }
}

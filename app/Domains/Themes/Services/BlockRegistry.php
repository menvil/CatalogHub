<?php

namespace App\Domains\Themes\Services;

use App\Models\BlockDefinition;
use Illuminate\Database\Eloquent\Collection;

final class BlockRegistry
{
    /** @return Collection<int, BlockDefinition> */
    public function activeBlocks(): Collection
    {
        return BlockDefinition::query()->active()->orderBy('name')->get();
    }

    public function findByCode(string $code): ?BlockDefinition
    {
        return BlockDefinition::query()->where('code', $code)->first();
    }

    /** @return Collection<int, BlockDefinition> */
    public function forPageType(string $pageType): Collection
    {
        return $this->activeBlocks()
            ->filter(fn (BlockDefinition $block): bool => $block->supportsPage($pageType))
            ->values();
    }

    public function blockSupportsPage(string $blockCode, string $pageType): bool
    {
        $block = $this->findByCode($blockCode);

        return $block instanceof BlockDefinition && $block->isActive() && $block->supportsPage($pageType);
    }
}

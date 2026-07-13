<?php

namespace App\Domains\Themes\Actions;

use App\Models\Site;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class ReorderSiteHomeBlocksAction
{
    /** @param list<int> $orderedIds */
    public function handle(Site $site, array $orderedIds): void
    {
        DB::transaction(function () use ($site, $orderedIds): void {
            $lockedSite = Site::query()->lockForUpdate()->findOrFail($site->getKey());
            $blocks = $lockedSite->homeBlocks()->lockForUpdate()->get()->keyBy('id');

            if (count($orderedIds) !== count(array_unique($orderedIds))
                || $blocks->keys()->sort()->values()->all() !== collect($orderedIds)->sort()->values()->all()) {
                throw ValidationException::withMessages(['order' => 'The block order must include every site home block exactly once.']);
            }

            $blockCount = count($orderedIds);
            $stagingStart = ((int) $blocks->max('position')) + $blockCount + 1;
            if ($blockCount > 0 && $stagingStart + $blockCount - 1 > 4_294_967_295) {
                throw ValidationException::withMessages(['order' => 'Block positions are too large to reorder safely.']);
            }
            foreach ($orderedIds as $index => $id) {
                $blocks->get($id)?->update(['position' => $stagingStart + $index]);
            }

            foreach ($orderedIds as $index => $id) {
                $blocks->get($id)?->update(['position' => $index + 1]);
            }
        });
    }
}

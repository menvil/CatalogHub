<?php

namespace App\Jobs\Projections;

use App\Domains\Projections\SiteSyncService;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

final class RebuildPriceAffectedProjectionJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 300;

    public function __construct(
        public int $siteId,
        public int $centralProductId,
    ) {}

    public function uniqueId(): string
    {
        return $this->siteId.':'.$this->centralProductId;
    }

    public function handle(?SiteSyncService $sync = null): void
    {
        $site = Site::query()->find($this->siteId);
        $product = CentralProduct::query()->find($this->centralProductId);

        if (! $site instanceof Site || ! $product instanceof CentralProduct) {
            return;
        }

        if (! SiteProduct::query()
            ->where('site_id', $site->id)
            ->where('central_product_id', $product->id)
            ->where('visibility', 'visible')
            ->exists()) {
            return;
        }

        $sync ??= app(SiteSyncService::class);
        $locales = DB::table('site_locales')
            ->where('site_id', $site->id)
            ->where('is_enabled', true)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('locale_code')
            ->map(fn (mixed $locale): string => (string) $locale)
            ->all();

        if ($locales === []) {
            $locales = [(string) $site->default_locale];
        }

        foreach ($locales as $locale) {
            $sync->syncProduct($site, $product, $locale);
        }
    }
}

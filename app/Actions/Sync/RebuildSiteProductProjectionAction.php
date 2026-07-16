<?php

namespace App\Actions\Sync;

use App\Domains\Projections\SiteSyncService;
use App\Models\SiteProduct;
use App\Models\User;
use App\Services\Sync\SyncLogWriter;
use Illuminate\Auth\Access\AuthorizationException;
use Throwable;

final class RebuildSiteProductProjectionAction
{
    public function __construct(
        private readonly SiteSyncService $siteSyncService,
        private readonly SyncLogWriter $syncLogWriter,
    ) {}

    public function handle(
        User $admin,
        SiteProduct $siteProduct,
        string $triggeredBy = 'user',
    ): SiteProduct {
        if (! $admin->hasCatalogHubPermission('central.manage')) {
            throw new AuthorizationException('Only a central administrator can rebuild projections.');
        }

        $siteProduct->loadMissing(['site.locales', 'centralProduct']);
        $site = $siteProduct->site;
        $product = $siteProduct->centralProduct;
        $siteProduct->forceFill(['sync_status' => 'running'])->save();
        $locales = $site->locales
            ->where('is_enabled', true)
            ->sortBy('position')
            ->pluck('locale_code')
            ->all();

        if ($locales === []) {
            $locales = [(string) $site->default_locale];
        }

        try {
            foreach ($locales as $locale) {
                $this->siteSyncService->syncProduct($site, $product, (string) $locale);
            }

            $siteProduct->forceFill([
                'published_version' => $product->version,
                'last_synced_at' => now(),
                'sync_status' => 'completed',
            ])->save();

            $this->syncLogWriter->completed(
                operation: 'rebuild_product_projection',
                triggeredBy: $triggeredBy,
                actor: $admin,
                site: $site,
                product: $product,
                affectedCount: count($locales),
                context: ['locales' => $locales],
            );

            return $siteProduct;
        } catch (Throwable $exception) {
            $siteProduct->forceFill(['sync_status' => 'failed'])->save();
            $this->syncLogWriter->failed(
                operation: 'rebuild_product_projection',
                triggeredBy: $triggeredBy,
                error: $exception,
                actor: $admin,
                site: $site,
                product: $product,
                context: ['locales' => $locales],
            );

            throw $exception;
        }
    }
}

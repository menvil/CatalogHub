<?php

namespace App\Console\Commands;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\Projections\SiteSyncService;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use Illuminate\Console\Command;
use Throwable;

final class SyncProductProjectionCommand extends Command
{
    protected $signature = 'cataloghub:sync-product
                            {site : Site ID or code}
                            {product : Central product ID}
                            {--locale= : Projection locale}';

    protected $description = 'Build and persist one product projection for a site';

    public function handle(SiteSyncService $syncService): int
    {
        $siteIdentifier = (string) $this->argument('site');
        $site = ctype_digit($siteIdentifier)
            ? Site::query()->find((int) $siteIdentifier)
            : null;
        $site ??= Site::query()->where('code', $siteIdentifier)->first();

        if (! $site instanceof Site) {
            $this->error("Site not found: {$siteIdentifier}");

            return self::FAILURE;
        }

        $productIdentifier = (string) $this->argument('product');
        $product = ctype_digit($productIdentifier)
            ? CentralProduct::query()->find((int) $productIdentifier)
            : null;

        if (! $product instanceof CentralProduct) {
            $this->error("Product not found: {$productIdentifier}");

            return self::FAILURE;
        }

        $localeOption = $this->option('locale');
        $locale = is_string($localeOption) && $localeOption !== '' ? $localeOption : null;

        try {
            $projection = $syncService->syncProduct($site, $product, $locale);
        } catch (Throwable $exception) {
            $this->error('Product projection sync failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $projectionStatus = $projection->getAttribute('status');
        $this->info(sprintf(
            'Product projection synced: site=%s product=%d locale=%s status=%s',
            $site->code,
            $product->getKey(),
            $projection->locale,
            $projectionStatus instanceof ProjectionStatus
                ? $projectionStatus->value
                : (string) $projectionStatus,
        ));

        return self::SUCCESS;
    }
}

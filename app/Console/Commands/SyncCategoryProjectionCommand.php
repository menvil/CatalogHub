<?php

namespace App\Console\Commands;

use App\Domains\Projections\Enums\ProjectionStatus;
use App\Domains\Projections\SiteSyncService;
use App\Models\CentralCatalog\CentralCategory;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteProduct;
use Illuminate\Console\Command;
use Throwable;

final class SyncCategoryProjectionCommand extends Command
{
    protected $signature = 'cataloghub:sync-category
                            {site : Site ID or code}
                            {category : Central category ID}
                            {--locale= : Projection locale}
                            {--with-products : Also sync visible site products in the category}';

    protected $description = 'Build and persist one category projection for a site';

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

        $categoryIdentifier = (string) $this->argument('category');
        $category = ctype_digit($categoryIdentifier)
            ? CentralCategory::query()->find((int) $categoryIdentifier)
            : null;

        if (! $category instanceof CentralCategory) {
            $this->error("Category not found: {$categoryIdentifier}");

            return self::FAILURE;
        }

        $localeOption = $this->option('locale');
        $locale = is_string($localeOption) && $localeOption !== '' ? $localeOption : null;
        $productCount = 0;

        try {
            $projection = $syncService->syncCategory($site, $category, $locale);

            if ($this->option('with-products')) {
                $productIds = SiteProduct::query()
                    ->where('site_id', $site->getKey())
                    ->where('visibility', 'visible')
                    ->pluck('central_product_id');
                $products = CentralProduct::query()
                    ->where('central_category_id', $category->getKey())
                    ->whereKey($productIds)
                    ->orderBy('id')
                    ->get();

                foreach ($products as $product) {
                    $syncService->syncProduct($site, $product, $projection->locale);
                    $productCount++;
                }
            }
        } catch (Throwable $exception) {
            $this->error('Category projection sync failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $projectionStatus = $projection->getAttribute('status');
        $this->info(sprintf(
            'Category projection synced: site=%s category=%d locale=%s status=%s products=%d',
            $site->code,
            $category->getKey(),
            $projection->locale,
            $projectionStatus instanceof ProjectionStatus
                ? $projectionStatus->value
                : (string) $projectionStatus,
            $productCount,
        ));

        return self::SUCCESS;
    }
}

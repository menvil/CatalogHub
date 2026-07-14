<?php

namespace App\Console\Commands;

use App\Domains\Projections\SiteSyncService;
use App\Models\Site;
use Illuminate\Console\Command;
use Throwable;

final class SyncSiteProjectionsCommand extends Command
{
    protected $signature = 'cataloghub:sync-site
                            {site : Site ID or code}
                            {--locale= : Sync only this locale}
                            {--products-only : Sync product projections only}
                            {--categories-only : Sync category projections only}
                            {--force : Rebuild selected projections regardless of their current status}';

    protected $description = 'Build and persist all selected projections for a site';

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

        $productsOnly = (bool) $this->option('products-only');
        $categoriesOnly = (bool) $this->option('categories-only');

        if ($productsOnly && $categoriesOnly) {
            $this->error('The --products-only and --categories-only options cannot be combined.');

            return self::INVALID;
        }

        $localeOption = $this->option('locale');
        $locale = is_string($localeOption) && $localeOption !== '' ? $localeOption : null;

        try {
            $counts = $syncService->syncSite($site, $locale, $productsOnly, $categoriesOnly);
        } catch (Throwable $exception) {
            $this->error('Site projection sync failed: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Site projections synced: site=%s locales=%d categories=%d products=%d%s',
            $site->code,
            $counts['locales'],
            $counts['categories'],
            $counts['products'],
            $this->option('force') ? ' force=yes' : '',
        ));

        return self::SUCCESS;
    }
}

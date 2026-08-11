<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use Illuminate\Console\Command;

final class VerifyFoundationInstall extends Command
{
    protected $signature = 'foundation:verify';

    protected $description = 'Verify the deterministic Section Zero foundation fixture graph';

    public function handle(): int
    {
        $siteCodes = ['tech-germany', 'monitors-germany', 'archived-germany'];
        $siteIds = Site::query()->whereIn('code', $siteCodes)->pluck('id');
        $demoUsers = User::query()->where('email', 'like', '%@demo.cataloghub.test');
        $failures = [];

        if ($siteIds->count() !== 3) {
            $failures[] = 'Expected 3 foundation demo sites.';
        }

        if ($demoUsers->count() !== 8) {
            $failures[] = 'Expected 8 foundation demo users.';
        }

        if (SiteMembership::query()->whereIn('user_id', $demoUsers->pluck('id'))->count() !== 6) {
            $failures[] = 'Expected 6 foundation demo memberships.';
        }

        $catalogRecords = CentralBrand::query()->count()
            + CentralProduct::query()->count()
            + Site::query()->whereKey($siteIds)->withCount(['categories', 'products', 'homeBlocks'])->get()
                ->sum(fn (Site $site): int => $site->categories_count + $site->products_count + $site->home_blocks_count);

        if ($catalogRecords !== 0) {
            $failures[] = 'Foundation bootstrap must not create business or catalog data.';
        }

        if ($failures !== []) {
            foreach ($failures as $failure) {
                $this->error($failure);
            }

            return self::FAILURE;
        }

        $this->info('Foundation install verified: 3 sites, 8 users, 6 memberships, 0 catalog records.');

        return self::SUCCESS;
    }
}

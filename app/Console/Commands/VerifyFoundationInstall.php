<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\CentralCatalog\CatalogTag;
use App\Models\CentralCatalog\CentralBrand;
use App\Models\CentralCatalog\CentralProduct;
use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use Database\Seeders\FoundationDemoUsersSeeder;
use Database\Seeders\SiteFoundationSeeder;
use Illuminate\Console\Command;

final class VerifyFoundationInstall extends Command
{
    protected $signature = 'foundation:verify';

    protected $description = 'Verify the deterministic Section Zero foundation fixture graph';

    public function handle(): int
    {
        $expectedSites = count(SiteFoundationSeeder::SITE_CODES);
        $expectedUsers = count(FoundationDemoUsersSeeder::PERSONAS);
        $expectedMemberships = array_sum(array_map(
            static fn (array $persona): int => count($persona['memberships']),
            FoundationDemoUsersSeeder::PERSONAS,
        ));
        $demoEmails = array_column(FoundationDemoUsersSeeder::PERSONAS, 'email');
        $siteIds = Site::query()->whereIn('code', SiteFoundationSeeder::SITE_CODES)->pluck('id');
        $demoUsers = User::query()->whereIn('email', $demoEmails);
        $failures = [];

        if ($siteIds->count() !== $expectedSites) {
            $failures[] = "Expected {$expectedSites} foundation demo sites.";
        }

        if ($demoUsers->count() !== $expectedUsers) {
            $failures[] = "Expected {$expectedUsers} foundation demo users.";
        }

        if (SiteMembership::query()->whereIn('user_id', $demoUsers->pluck('id'))->count() !== $expectedMemberships) {
            $failures[] = "Expected {$expectedMemberships} foundation demo memberships.";
        }

        $catalogRecords = CentralBrand::query()->count()
            + CatalogTag::query()->count()
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

        $this->info("Foundation install verified: {$expectedSites} sites, {$expectedUsers} users, {$expectedMemberships} memberships, 0 catalog records.");

        return self::SUCCESS;
    }
}

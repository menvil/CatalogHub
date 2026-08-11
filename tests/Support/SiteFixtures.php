<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Enums\SiteMode;
use App\Enums\SiteStatus;
use App\Models\Site;

trait SiteFixtures
{
    /** @param list<string> $locales */
    protected function foundationSite(
        string $key = 'alpha',
        array $locales = ['en-US'],
        SiteMode $mode = SiteMode::MultiCategory,
        SiteStatus $status = SiteStatus::Active,
    ): Site {
        return Site::factory()
            ->withRuntimeContext($locales)
            ->create([
                ...FoundationVisualFixture::site($key),
                'mode' => $mode,
                'status' => $status,
            ]);
    }
}

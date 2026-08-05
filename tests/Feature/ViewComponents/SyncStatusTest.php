<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use App\View\Components\SiteAdmin\SyncStatus;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Tests\TestCase;

final class SyncStatusTest extends TestCase
{
    public function test_sync_status_renders_truthful_foundation_states_without_database_queries(): void
    {
        DB::enableQueryLog();

        $notConfigured = Blade::render('<x-site-admin.sync-status />');
        $unknown = Blade::render('<x-site-admin.sync-status state="unknown" />');

        $this->assertStringContainsString('data-site-sync-status="not-configured"', $notConfigured);
        $this->assertStringContainsString('Not configured', $notConfigured);
        $this->assertStringContainsString('data-site-sync-status="unknown"', $unknown);
        $this->assertStringContainsString('Unknown', $unknown);
        $this->assertStringNotContainsString('Last sync', $notConfigured.$unknown);
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_sync_status_rejects_deceptive_or_unsupported_states(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new SyncStatus('completed');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComponents;

use App\Enums\SiteStatus;
use App\Models\Market;
use App\Models\Site;
use App\Services\Sites\SiteContextValueResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SiteContextHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_header_renders_resolved_site_domain_market_locale_and_active_status_without_queries(): void
    {
        $market = Market::factory()->create(['name' => 'DACH & <EU>']);
        $site = Site::factory()->active()->withRuntimeContext(['de-DE'])->create([
            'market_id' => $market->id,
            'name' => 'Tech Germany',
        ]);
        $site->load(['market', 'domains', 'locales.locale']);
        $domain = $site->domains->firstOrFail();
        $context = app(SiteContextValueResolver::class)->resolve($site, $domain, 'de-DE');

        DB::flushQueryLog();
        DB::enableQueryLog();
        $html = Blade::render('<x-site-admin.site-context-header :context="$context" />', compact('context'));

        $this->assertSame([], DB::getQueryLog());
        $this->assertStringContainsString('Tech Germany', $html);
        $this->assertStringContainsString((string) $domain->host, $html);
        $this->assertStringContainsString('DACH &amp; &lt;EU&gt;', $html);
        $this->assertStringContainsString('de-DE', $html);
        $this->assertStringContainsString('Active', $html);
        $this->assertStringContainsString('data-admin-status-badge="success"', $html);
    }

    public function test_suspended_site_and_long_domain_remain_explicit(): void
    {
        $site = Site::factory()->withRuntimeContext()->create([
            'name' => 'Suspended editorial workspace',
            'domain' => 'a-deliberately-long-site-administration-domain.example.test',
            'status' => SiteStatus::Suspended,
        ]);
        $site->load(['market', 'domains', 'locales.locale']);
        $domain = $site->domains->firstOrFail();
        $context = app(SiteContextValueResolver::class)->resolve($site, $domain, null);

        $html = Blade::render('<x-site-admin.site-context-header :context="$context" />', compact('context'));

        $this->assertStringContainsString((string) $domain->host, $html);
        $this->assertStringContainsString('Suspended', $html);
        $this->assertStringContainsString('data-admin-status-badge="warning"', $html);
        $this->assertStringContainsString('break-all', $html);
    }
}

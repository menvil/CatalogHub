<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Site;
use Database\Seeders\FoundationDemoSeeder;
use Database\Seeders\SiteFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicHomeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(FoundationDemoSeeder::class);
    }

    public function test_active_bare_hosts_redirect_to_their_default_locale(): void
    {
        foreach ([
            SiteFoundationSeeder::TECH_HOST,
            SiteFoundationSeeder::MONITORS_HOST,
        ] as $host) {
            $this->get("http://{$host}/")
                ->assertRedirect('/de-DE');
        }
    }

    public function test_active_alias_host_uses_the_same_runtime_resolution_pipeline(): void
    {
        $this->get('http://'.SiteFoundationSeeder::TECH_ALIAS_HOST.'/')
            ->assertRedirect('/de-DE');
    }

    public function test_bare_host_redirect_uses_site_configuration_instead_of_a_hardcoded_locale(): void
    {
        $site = Site::query()->where('code', 'monitors-germany')->firstOrFail();
        $site->update(['default_locale' => 'en-DE']);
        $site->locales()->update(['is_default' => false]);
        $site->locales()->where('locale_code', 'en-DE')->update(['is_default' => true]);

        $this->get('http://'.SiteFoundationSeeder::MONITORS_HOST.'/')
            ->assertRedirect('/en-DE');
    }

    public function test_unknown_and_archived_bare_hosts_fail_closed(): void
    {
        foreach (['unknown.cataloghub.test', SiteFoundationSeeder::ARCHIVED_HOST] as $host) {
            $this->get("http://{$host}/")
                ->assertNotFound()
                ->assertDontSee('Tech Germany')
                ->assertDontSee('Monitors Germany')
                ->assertDontSee('CatalogHub public demo placeholder');
        }
    }
}

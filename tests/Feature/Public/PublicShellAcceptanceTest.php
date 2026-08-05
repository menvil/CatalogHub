<?php

declare(strict_types=1);

namespace Tests\Feature\Public;

use App\Support\DesignSystem\PublicShellFixture;
use Database\Seeders\SiteFoundationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PublicShellAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_two_foundation_hosts_render_their_whitelisted_shells_locale_links_and_seo(): void
    {
        $this->seed(SiteFoundationSeeder::class);

        $multi = $this->get('http://'.SiteFoundationSeeder::TECH_HOST.'/de-DE')
            ->assertOk()
            ->assertSee('data-public-layout="multi-category"', false)
            ->assertSee('data-public-theme="cataloghub-multi"', false)
            ->assertSee('<link rel="canonical" href="https://'.SiteFoundationSeeder::TECH_HOST.'/de-DE">', false)
            ->assertSee('href="https://'.SiteFoundationSeeder::TECH_HOST.'/en-DE"', false);

        $single = $this->get('http://'.SiteFoundationSeeder::MONITORS_HOST.'/en-DE')
            ->assertOk()
            ->assertSee('data-public-layout="single-category"', false)
            ->assertSee('data-public-theme="cataloghub-single"', false)
            ->assertSee('<link rel="canonical" href="https://'.SiteFoundationSeeder::MONITORS_HOST.'/en-DE">', false)
            ->assertSee('href="https://'.SiteFoundationSeeder::MONITORS_HOST.'/de-DE"', false);

        foreach ([$multi->getContent(), $single->getContent()] as $html) {
            $this->assertStringContainsString('/build/assets/public-', $html);
            $this->assertStringNotContainsString('/build/assets/central-admin-', $html);
            $this->assertStringNotContainsString('/build/assets/site-admin-', $html);
            $this->assertStringNotContainsString('data-central-shell', $html);
            $this->assertStringNotContainsString('data-site-shell', $html);
        }
    }

    public function test_local_preview_exposes_only_deterministic_supported_states(): void
    {
        foreach (PublicShellFixture::STATES as $state) {
            $this->get('/dev/public-shell?state='.$state)
                ->assertOk()
                ->assertSee('data-public-shell-fixture="'.PublicShellFixture::VERSION.'"', false)
                ->assertSee('data-public-preview-state="'.$state.'"', false);
        }

        $this->get('/dev/public-shell?state=unsupported')
            ->assertRedirect()
            ->assertSessionHasErrors('state');
        $this->get('/dev/public-shell?acceptance=eventually')
            ->assertRedirect()
            ->assertSessionHasErrors('acceptance');
    }
}

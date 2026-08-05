<?php

declare(strict_types=1);

namespace Tests\Feature\SiteAdmin;

use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SitePageHeaderTest extends TestCase
{
    use RefreshDatabase;

    public function test_site_dashboard_uses_the_shared_header_and_site_scoped_breadcrumb(): void
    {
        $site = Site::factory()->active()->withRuntimeContext()->create(['name' => 'Header portal']);
        $user = User::factory()->siteAdmin($site)->create();

        $response = $this->actingAs($user)->get(route('filament.site.pages.home', [
            'site_id' => $site->getKey(),
        ]));

        $response
            ->assertOk()
            ->assertSee('data-screen-id="SA-001"', false)
            ->assertSee('Site dashboard')
            ->assertSee('Header portal')
            ->assertSee('site_id='.$site->getKey(), false);

        $this->assertSame(1, substr_count((string) $response->getContent(), '<h1'));
    }
}

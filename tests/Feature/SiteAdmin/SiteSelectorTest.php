<?php

declare(strict_types=1);

namespace Tests\Feature\SiteAdmin;

use App\Enums\SiteStatus;
use App\Models\Site;
use App\Models\SiteMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

final class SiteSelectorTest extends TestCase
{
    use RefreshDatabase;

    public function test_selector_lists_only_authorized_administrable_sites_with_explicit_urls(): void
    {
        $current = Site::factory()->active()->withRuntimeContext()->create(['name' => 'Current portal']);
        $assigned = Site::factory()->active()->withRuntimeContext()->create(['name' => 'Assigned portal']);
        $unassigned = Site::factory()->active()->withRuntimeContext()->create(['name' => 'Unassigned portal']);
        $archived = Site::factory()->withRuntimeContext()->create([
            'name' => 'Archived portal',
            'status' => SiteStatus::Archived,
        ]);
        $user = User::factory()->siteAdmin($current)->create();
        SiteMembership::factory()->for($user)->for($assigned)->create();
        SiteMembership::factory()->for($user)->for($archived)->create();

        $html = Blade::render(
            '<x-site-admin.site-selector :current-site="$current" :user="$user" />',
            compact('current', 'user'),
        );

        $this->assertStringContainsString('Current portal', $html);
        $this->assertStringContainsString('Assigned portal', $html);
        $this->assertStringContainsString('aria-current="true"', $html);
        $this->assertStringContainsString('site_id='.$assigned->getKey(), $html);
        $this->assertStringNotContainsString('Unassigned portal', $html);
        $this->assertStringNotContainsString('Archived portal', $html);
        $this->assertStringNotContainsString('site_id='.$unassigned->getKey(), $html);
    }

    public function test_one_site_user_gets_an_unambiguous_non_switching_state(): void
    {
        $site = Site::factory()->active()->withRuntimeContext()->create(['name' => 'Only portal']);
        $user = User::factory()->siteAdmin($site)->create();

        $html = Blade::render(
            '<x-site-admin.site-selector :current-site="$site" :user="$user" />',
            compact('site', 'user'),
        );

        $this->assertStringContainsString('Only portal', $html);
        $this->assertStringContainsString('Only assigned site', $html);
        $this->assertStringNotContainsString('<details', $html);
    }

    public function test_switching_uses_the_selected_authorized_runtime_context(): void
    {
        $first = Site::factory()->active()->withRuntimeContext()->create(['name' => 'First portal']);
        $second = Site::factory()->active()->withRuntimeContext()->create(['name' => 'Second portal']);
        $user = User::factory()->siteAdmin($first)->create();
        SiteMembership::factory()->for($user)->for($second)->create();

        $this->actingAs($user)
            ->get(route('filament.site.pages.home', ['site_id' => $second->getKey()]))
            ->assertOk()
            ->assertSee('Second portal')
            ->assertDontSee('Current site: First portal');

        $this->get(route('filament.site.pages.home', ['site_id' => 999999]))
            ->assertForbidden()
            ->assertDontSee('Second portal');
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Presentation;

use App\Enums\UserRole;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class LayoutIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_context_shell_renders_its_unique_root_marker(): void
    {
        $central = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $site = Site::factory()->create();
        $siteAdmin = User::factory()->siteAdmin($site)->create();
        $this->seed(MultiCategorySiteSeeder::class);

        $this->actingAs($central)
            ->get('/admin/central')
            ->assertOk()
            ->assertSee('data-presentation-context="central-admin"', false)
            ->assertDontSee('data-presentation-context="site-admin"', false)
            ->assertDontSee('data-presentation-context="public-site"', false);

        $this->actingAs($siteAdmin)
            ->get('/admin/site')
            ->assertOk()
            ->assertSee('data-presentation-context="site-admin"', false)
            ->assertDontSee('data-presentation-context="central-admin"', false)
            ->assertDontSee('data-presentation-context="public-site"', false);

        auth()->logout();
        $this->get('http://tech-compare.test/en-US')
            ->assertOk()
            ->assertSee('data-presentation-context="public-site"', false)
            ->assertDontSee('data-presentation-context="central-admin"', false)
            ->assertDontSee('data-presentation-context="site-admin"', false)
            ->assertDontSee('Central Admin')
            ->assertDontSee('Site Admin');
    }

    public function test_context_layouts_own_distinct_asset_entry_points(): void
    {
        $central = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $site = Site::factory()->create(['name' => 'Deterministic Site shell']);
        $siteAdmin = User::factory()->siteAdmin($site)->create();
        $this->seed(MultiCategorySiteSeeder::class);

        $this->actingAs($central)
            ->get('/admin/central')
            ->assertOk()
            ->assertSee('/build/assets/central-admin-', false)
            ->assertSee('Central Admin')
            ->assertSee('Central Admin shell');

        $this->actingAs($siteAdmin)
            ->get('/admin/site')
            ->assertOk()
            ->assertSee('/build/assets/site-admin-', false)
            ->assertSee('CatalogHub Site')
            ->assertSee('Site Admin shell');

        auth()->logout();
        $this->get('http://tech-compare.test/en-US')
            ->assertOk()
            ->assertSee('/build/assets/public-', false)
            ->assertSee('aria-label="Primary navigation"', false)
            ->assertSee('Search');
    }

    public function test_public_layout_has_no_admin_navigation_or_asset_dependency(): void
    {
        $this->seed(MultiCategorySiteSeeder::class);

        $this->get('http://tech-compare.test/en-US')
            ->assertOk()
            ->assertSee('/build/assets/public-', false)
            ->assertSee('aria-label="Primary navigation"', false)
            ->assertDontSee('Central Admin')
            ->assertDontSee('Site Admin')
            ->assertDontSee('/build/assets/central-admin-', false)
            ->assertDontSee('/build/assets/site-admin-', false);
    }
}

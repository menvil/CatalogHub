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
            ->assertDontSee('data-presentation-context="site-admin"', false);

        $this->actingAs($siteAdmin)
            ->get('/admin/site')
            ->assertOk()
            ->assertSee('data-presentation-context="site-admin"', false)
            ->assertDontSee('data-presentation-context="central-admin"', false);

        auth()->logout();
        $this->get('http://tech-compare.test/en-US')
            ->assertOk()
            ->assertSee('data-presentation-context="public-site"', false)
            ->assertDontSee('Central Admin')
            ->assertDontSee('Site Admin');
    }

    public function test_context_layouts_own_distinct_asset_entry_points(): void
    {
        $layouts = [
            'resources/views/layouts/central-admin.blade.php' => 'resources/css/central-admin.css',
            'resources/views/layouts/site-admin.blade.php' => 'resources/css/site-admin.css',
            'resources/views/public/layouts/app.blade.php' => 'resources/css/public.css',
        ];

        foreach ($layouts as $layout => $asset) {
            $contents = (string) file_get_contents(base_path($layout));

            self::assertFileExists(base_path($asset));
            self::assertStringContainsString($asset, $contents);
        }
    }

    public function test_public_layout_has_no_admin_navigation_or_asset_dependency(): void
    {
        $contents = (string) file_get_contents(resource_path('views/public/layouts/app.blade.php'));

        self::assertStringNotContainsString('x-admin.', $contents);
        self::assertStringNotContainsString('central-admin.css', $contents);
        self::assertStringNotContainsString('site-admin.css', $contents);
    }
}

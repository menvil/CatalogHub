<?php

declare(strict_types=1);

namespace Tests\Feature\Smoke;

use App\Enums\UserRole;
use App\Models\Site;
use App\Models\User;
use Database\Seeders\Demo\MultiCategorySiteSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('smoke')]
final class PresentationContextsSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_site_and_public_shells_open_independently(): void
    {
        $centralAdmin = User::factory()->create(['role' => UserRole::CentralAdmin]);
        $site = Site::factory()->create();
        $siteAdmin = User::factory()->siteAdmin($site)->create();
        $this->seed(MultiCategorySiteSeeder::class);

        $this->actingAs($centralAdmin)
            ->get('/admin/central')
            ->assertOk()
            ->assertSee('data-presentation-context="central-admin"', false);

        $this->actingAs($siteAdmin)
            ->get('/admin/site')
            ->assertOk()
            ->assertSee('data-presentation-context="site-admin"', false);

        auth()->logout();
        $this->get('http://tech-compare.test/en-US')
            ->assertOk()
            ->assertSee('data-presentation-context="public-site"', false);
    }
}

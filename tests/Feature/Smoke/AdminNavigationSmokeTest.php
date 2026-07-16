<?php

namespace Tests\Feature\Smoke;

use App\Filament\Pages\CentralDashboard;
use App\Filament\Pages\TranslationDashboard;
use App\Filament\Resources\CatalogSnapshotResource;
use App\Filament\Resources\CentralCategoryResource;
use App\Filament\Resources\CentralProductResource;
use App\Filament\Resources\ImportBatchResource;
use App\Filament\Resources\PriceSourceResource;
use App\Filament\Resources\SiteResource;
use App\Models\Site;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('smoke')]
class AdminNavigationSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_central_admin_opens_key_operational_pages(): void
    {
        $this->actingAs(User::factory()->centralAdmin()->create());

        $urls = [
            'dashboard' => CentralDashboard::getUrl(),
            'products' => CentralProductResource::getUrl('index'),
            'categories' => CentralCategoryResource::getUrl('index'),
            'imports' => ImportBatchResource::getUrl('index'),
            'media' => route('central.media.index'),
            'translations' => TranslationDashboard::getUrl(),
            'price sources' => PriceSourceResource::getUrl('index'),
            'sites' => SiteResource::getUrl('index'),
            'snapshots' => CatalogSnapshotResource::getUrl('index'),
        ];

        foreach ($urls as $label => $url) {
            $response = $this->get($url);

            $this->assertTrue($response->isOk(), "The {$label} page failed with HTTP {$response->getStatusCode()}.");
        }
    }

    public function test_guest_is_redirected_to_admin_login(): void
    {
        $this->get(CentralDashboard::getUrl())
            ->assertRedirect('/admin/login');
    }

    public function test_site_scoped_admin_cannot_open_central_snapshot_history(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->siteAdmin($site)->create())
            ->get(CatalogSnapshotResource::getUrl('index'))
            ->assertForbidden();
    }
}

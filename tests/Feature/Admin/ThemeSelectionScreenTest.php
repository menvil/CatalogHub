<?php

namespace Tests\Feature\Admin;

use App\Enums\ThemeStatus;
use App\Enums\UserRole;
use App\Filament\Resources\SiteResource\Pages\ThemeSelection;
use App\Models\Site;
use App\Models\SiteFeature;
use App\Models\Theme;
use App\Models\ThemeManifestRecord;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThemeSelectionScreenTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_admin_sees_active_themes_and_compatibility(): void
    {
        $site = Site::factory()->create();
        SiteFeature::query()->create([
            'site_id' => $site->id,
            'feature_key' => 'reviews',
            'is_enabled' => true,
        ]);

        $compatible = Theme::factory()->create([
            'name' => 'Catalog Clean',
            'status' => ThemeStatus::Active,
        ]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $compatible->id,
            'manifest_json' => $this->manifest($compatible->code, ['latest_reviews']),
            'supports_json' => ['latest_reviews'],
            'layouts_json' => ['home' => 'home-clean'],
        ]);

        $incompatible = Theme::factory()->create([
            'name' => 'Minimal Theme',
            'status' => ThemeStatus::Active,
        ]);
        ThemeManifestRecord::query()->create([
            'theme_id' => $incompatible->id,
            'manifest_json' => $this->manifest($incompatible->code, []),
            'supports_json' => [],
            'layouts_json' => ['home' => 'home-minimal'],
        ]);

        Theme::factory()->create([
            'name' => 'Archived Theme',
            'status' => ThemeStatus::Archived,
        ]);

        $this->actingAs(User::factory()->centralAdmin()->create())
            ->get(ThemeSelection::getUrl(['record' => $site]))
            ->assertOk()
            ->assertSee('Catalog Clean')
            ->assertSee('Compatible')
            ->assertSee('Minimal Theme')
            ->assertSee('Incompatible')
            ->assertSee('Missing site features: reviews')
            ->assertDontSee('Archived Theme');
    }

    public function test_user_without_site_settings_permission_cannot_open_screen(): void
    {
        $site = Site::factory()->create();

        $this->actingAs(User::factory()->create(['role' => UserRole::CatalogEditor]))
            ->get(ThemeSelection::getUrl(['record' => $site]))
            ->assertForbidden();
    }

    /** @param list<string> $supports */
    private function manifest(string $code, array $supports): array
    {
        return [
            'code' => $code,
            'name' => str($code)->headline()->toString(),
            'supports' => $supports,
            'layouts' => ['home' => 'home-clean'],
        ];
    }
}

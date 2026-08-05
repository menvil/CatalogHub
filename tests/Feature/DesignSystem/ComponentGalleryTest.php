<?php

declare(strict_types=1);

namespace Tests\Feature\DesignSystem;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\DesignSystem\FoundationDesignSystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;
use Tests\TestCase;

final class ComponentGalleryTest extends TestCase
{
    use RefreshDatabase;

    public function test_gallery_requires_central_access(): void
    {
        $this->get('/admin/central/component-gallery')
            ->assertRedirect(route('filament.central.auth.login'));

        $siteAdmin = User::factory()->create(['role' => UserRole::SiteAdmin]);

        $this->actingAs($siteAdmin)
            ->get('/admin/central/component-gallery')
            ->assertForbidden();
    }

    public function test_central_admin_opens_the_deterministic_foundation_gallery(): void
    {
        $centralAdmin = User::factory()->create(['role' => UserRole::CentralAdmin]);

        $this->actingAs($centralAdmin)
            ->get('/admin/central/component-gallery')
            ->assertOk()
            ->assertSee('Foundation Component Gallery')
            ->assertSee('data-gallery-fixture="foundation-v1"', false)
            ->assertSee('Color and status tokens')
            ->assertSee('Typography')
            ->assertSee('Spacing and geometry')
            ->assertSee('Heroicons')
            ->assertSee('Responsive density')
            ->assertSee('/build/assets/central-admin-', false)
            ->assertDontSee('data-presentation-context="site-admin"', false)
            ->assertDontSee('data-presentation-context="public-site"', false);
    }

    public function test_local_capture_route_renders_the_same_deterministic_fixture_without_publishing_access(): void
    {
        $this->get('/dev/component-gallery')
            ->assertOk()
            ->assertSee('data-gallery-fixture="foundation-v1"', false)
            ->assertSee('data-presentation-context="central-admin"', false);
    }

    public function test_component_mode_renders_deterministic_forms_tables_feedback_and_acceptance_fixtures(): void
    {
        foreach (['forms', 'tables', 'feedback'] as $section) {
            $this->get('/dev/component-gallery?mode=components&section='.$section)
                ->assertOk()
                ->assertSee('data-admin-components-fixture="admin-components-v1"', false)
                ->assertSee('data-admin-components-section="'.$section.'"', false)
                ->assertSee('Admin component gallery');
        }

        $this->get('/dev/component-gallery?mode=components&section=acceptance&acceptance=1')
            ->assertOk()
            ->assertSee('data-admin-form-state', false)
            ->assertSee('data-admin-data-table', false)
            ->assertSee('data-gallery-modal-fixture', false)
            ->assertSee('data-browser-acceptance="pending"', false);
    }

    public function test_component_gallery_rejects_unknown_fixture_sections(): void
    {
        $this->get('/dev/component-gallery?mode=components&section=unknown')
            ->assertSessionHasErrors('section');
    }

    public function test_icon_contract_is_single_source_and_unknown_icons_fail_loudly(): void
    {
        foreach (FoundationDesignSystem::ICONS as $icon) {
            $this->assertArrayHasKey($icon['icon'], FoundationDesignSystem::HEROICON_COMPONENTS);
        }

        $this->expectException(ViewException::class);
        $this->expectExceptionMessage('Unknown foundation icon [misspelled-icon].');

        Blade::render('<x-ui.icon name="misspelled-icon" label="Incorrect semantic" />');
    }
}

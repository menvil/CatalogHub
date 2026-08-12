<?php

namespace Tests\Feature\Admin;

use App\Providers\Filament\CentralAdminPanelProvider;
use App\Providers\Filament\SiteAdminPanelProvider;
use Filament\Panel;
use Filament\Support\Enums\Width;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class AdminResponsiveSmokeTest extends TestCase
{
    public function test_central_admin_layout_contains_responsive_shell_classes(): void
    {
        $html = Blade::render(<<<'BLADE'
            @extends('layouts.central-admin', ['activeNav' => 'Dashboard'])

            @section('content')
                <div>Responsive central content</div>
            @endsection
        BLADE);

        $this->assertStringContainsString('overflow-x-hidden', $html);
        $this->assertStringContainsString('lg:flex', $html);
        $this->assertStringContainsString('data-central-sidebar-mobile-open="false"', $html);
        $this->assertStringContainsString('data-central-sidebar-collapse', $html);
        $this->assertStringContainsString('aria-controls="central-navigation"', $html);
        $this->assertStringContainsString('min-w-0 flex-1', $html);
        $this->assertStringContainsString('data-admin-workspace', $html);
        $this->assertStringContainsString('w-full', $html);
        $this->assertStringNotContainsString('max-w-7xl', $html);
        $this->assertStringContainsString('Responsive central content', $html);
    }

    public function test_site_admin_layout_contains_responsive_shell_classes(): void
    {
        $html = Blade::render(<<<'BLADE'
            @extends('layouts.site-admin', ['activeNav' => 'Dashboard'])

            @section('content')
                <div>Responsive site content</div>
            @endsection
        BLADE);

        $this->assertStringContainsString('overflow-x-hidden', $html);
        $this->assertStringContainsString('lg:flex', $html);
        $this->assertStringContainsString('data-site-sidebar-mobile-open="false"', $html);
        $this->assertStringContainsString('data-site-sidebar-collapse', $html);
        $this->assertStringContainsString('aria-controls="site-navigation"', $html);
        $this->assertStringContainsString('min-w-0 flex-1', $html);
        $this->assertStringContainsString('data-admin-workspace', $html);
        $this->assertStringContainsString('w-full', $html);
        $this->assertStringNotContainsString('max-w-7xl', $html);
        $this->assertStringContainsString('Responsive site content', $html);
    }

    public function test_filament_panels_use_the_full_application_workspace(): void
    {
        $central = (new CentralAdminPanelProvider($this->app))->panel(Panel::make());
        $site = (new SiteAdminPanelProvider($this->app))->panel(Panel::make());

        $this->assertSame(Width::Full, $central->getMaxContentWidth());
        $this->assertSame(Width::Full, $site->getMaxContentWidth());
    }

    public function test_visual_smoke_page_contains_internal_overflow_for_dense_compositions(): void
    {
        $this->get('/dev/admin-visual-smoke')
            ->assertOk()
            ->assertSee('overflow-x-auto', false)
            ->assertSee('min-w-[42rem]', false)
            ->assertSee('md:grid-cols-3', false)
            ->assertSee('xl:grid-cols-[minmax(0,2fr)_minmax(22rem,1fr)]', false);
    }
}

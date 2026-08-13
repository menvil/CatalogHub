<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class SiteAdminLayoutTest extends TestCase
{
    public function test_site_admin_layout_renders_site_context_sidebar_and_content_slot(): void
    {
        $html = Blade::render(<<<'BLADE'
            @extends('layouts.site-admin', [
                'activeNav' => 'dashboard',
                'pageTitle' => 'Portal operations',
                'siteLabel' => 'Sofia catalog',
                'marketLabel' => 'BG market',
                'localeLabel' => 'bg',
                'siteAdminNavigation' => [[
                    'id' => 'dashboard',
                    'label' => 'Dashboard',
                    'icon' => 'home',
                    'url' => '/admin/site?site_id=1',
                ]],
            ])

            @section('content')
                <div>Site admin slot content</div>
            @endsection
        BLADE);

        $this->assertStringContainsString('data-admin-layout="site"', $html);
        $this->assertStringContainsString('data-site-shell', $html);
        $this->assertStringContainsString('<header', $html);
        $this->assertStringContainsString('<main id="site-main-content"', $html);
        $this->assertStringContainsString('aria-label="Site Admin navigation"', $html);
        $this->assertStringContainsString('Site Admin', $html);
        $this->assertStringContainsString('Search site workspace', $html);
        $this->assertStringContainsString('Sofia catalog', $html);
        $this->assertStringContainsString('BG market', $html);
        $this->assertStringContainsString('Locale: bg', $html);
        $this->assertStringContainsString('Site admin slot content', $html);
        $this->assertStringContainsString('data-admin-shell-header', $html);
        $this->assertStringContainsString('data-admin-sidebar-header', $html);
        $this->assertStringNotContainsString('data-central-shell', $html);
        $this->assertStringNotContainsString('Central Admin navigation', $html);

        $this->assertStringContainsString('Dashboard', $html);

        $visibleText = preg_replace('/\s+/', ' ', strip_tags($html));
        $this->assertIsString($visibleText);

        foreach ([
            'Site Settings',
            'Categories',
            'Products',
            'Theme',
            'Blocks',
            'Sync',
            'Prices',
            'Reviews',
            'Leads',
            'Content',
            'Polls',
        ] as $label) {
            $this->assertStringNotContainsString($label, $visibleText);
        }

        $this->assertStringNotContainsString('href="#"', $html);
    }

    public function test_site_admin_layout_renders_the_active_navigation_item(): void
    {
        $html = Blade::render(<<<'BLADE'
            @extends('layouts.site-admin', [
                'activeNav' => 'dashboard',
                'pageTitle' => 'Dashboard',
                'siteAdminNavigation' => [[
                    'id' => 'dashboard',
                    'label' => 'Dashboard',
                    'icon' => 'home',
                    'url' => '/admin/site?site_id=1',
                ]],
            ])

            @section('content')
                <p>Dashboard shell content</p>
            @endsection
        BLADE);

        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('Dashboard shell content', $html);
    }
}

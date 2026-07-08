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
                'activeNav' => 'Dashboard',
                'pageTitle' => 'Portal operations',
                'siteLabel' => 'Sofia catalog',
                'marketLabel' => 'BG market',
                'localeLabel' => 'bg',
            ])

            @section('content')
                <div>Site admin slot content</div>
            @endsection
        BLADE);

        $this->assertStringContainsString('data-admin-layout="site"', $html);
        $this->assertStringContainsString('Site Admin', $html);
        $this->assertStringContainsString('Search site workspace', $html);
        $this->assertStringContainsString('Sofia catalog', $html);
        $this->assertStringContainsString('BG market', $html);
        $this->assertStringContainsString('Locale: bg', $html);
        $this->assertStringContainsString('Site admin slot content', $html);

        foreach ([
            'Dashboard',
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
            'Settings',
        ] as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_site_admin_layout_renders_active_nav_breadcrumbs_and_page_actions(): void
    {
        $html = Blade::render(<<<'BLADE'
            @extends('layouts.site-admin', ['activeNav' => 'Theme', 'pageTitle' => 'Theme'])

            @section('breadcrumbs')
                <span>Site</span>
                <span>Theme</span>
            @endsection

            @section('pageActions')
                <button type="button">Preview placeholder</button>
            @endsection

            @section('content')
                <p>Theme shell content</p>
            @endsection
        BLADE);

        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('Theme shell content', $html);
        $this->assertStringContainsString('Preview placeholder', $html);
        $this->assertStringContainsString('aria-label="Breadcrumbs"', $html);
    }
}

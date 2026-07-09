<?php

namespace Tests\Feature\Admin;

use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CentralAdminLayoutTest extends TestCase
{
    public function test_central_admin_layout_renders_sidebar_topbar_and_content_slot(): void
    {
        $html = Blade::render(<<<'BLADE'
            @extends('layouts.central-admin', ['activeNav' => 'Dashboard', 'pageTitle' => 'Catalog quality'])

            @section('content')
                <div>Central admin slot content</div>
            @endsection
        BLADE);

        $this->assertStringContainsString('data-admin-layout="central"', $html);
        $this->assertStringContainsString('Central Admin', $html);
        $this->assertStringContainsString('Search canonical catalog', $html);
        $this->assertStringContainsString('Notifications', $html);
        $this->assertStringContainsString('Profile', $html);
        $this->assertStringContainsString('Central admin slot content', $html);
        $this->assertStringContainsString('<title>Catalog quality - ', $html);
        $this->assertStringNotContainsString('href="#"', $html);

        foreach ([
            'Dashboard',
            'Products',
            'Categories',
            'Brands',
            'Imports',
            'Media',
            'Translations',
            'Price Sources',
            'Sites',
            'Sync',
            'Backups',
            'Settings',
        ] as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_central_admin_layout_renders_active_nav_breadcrumbs_and_page_actions(): void
    {
        $html = Blade::render(<<<'BLADE'
            @extends('layouts.central-admin', ['activeNav' => 'Products', 'pageTitle' => 'Products'])

            @section('breadcrumbs')
                <span>Admin</span>
                <span>Products</span>
            @endsection

            @section('pageActions')
                <button type="button">Create placeholder</button>
            @endsection

            @section('content')
                <p>Products shell content</p>
            @endsection
        BLADE);

        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('Products shell content', $html);
        $this->assertStringContainsString('Create placeholder', $html);
        $this->assertStringContainsString('aria-label="Breadcrumbs"', $html);
    }
}

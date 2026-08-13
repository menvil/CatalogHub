<?php

namespace Tests\Feature\Admin;

use App\Support\DesignSystem\CentralShellFixture;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class CentralAdminLayoutTest extends TestCase
{
    public function test_central_admin_layout_renders_sidebar_topbar_and_content_slot(): void
    {
        $html = Blade::render(<<<'BLADE'
            @extends('layouts.central-admin', [
                'activeNav' => 'dashboard',
                'pageTitle' => 'Catalog quality',
                'centralUser' => $centralUser,
            ])

            @section('content')
                <div>Central admin slot content</div>
            @endsection
        BLADE, ['centralUser' => CentralShellFixture::user()]);

        $this->assertStringContainsString('data-admin-layout="central"', $html);
        $this->assertStringContainsString('Central Admin', $html);
        $this->assertStringContainsString('Search unavailable', $html);
        $this->assertStringContainsString('Notifications unavailable', $html);
        $this->assertStringContainsString('Central Acceptance User', $html);
        $this->assertStringContainsString('Central admin slot content', $html);
        $this->assertStringContainsString('data-admin-shell-header', $html);
        $this->assertStringContainsString('data-admin-sidebar-header', $html);
        $this->assertStringContainsString('<title>Catalog quality - ', $html);
        $this->assertStringNotContainsString('href="#"', $html);

        foreach ([
            'Dashboard',
            'Catalog',
            'Imports',
            'Media',
            'Translations',
            'Changes',
            'Prices',
            'Snapshots',
        ] as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    public function test_central_admin_layout_renders_active_nav_breadcrumbs_and_page_actions(): void
    {
        $html = Blade::render(<<<'BLADE'
            @extends('layouts.central-admin', [
                'activeNav' => 'catalog',
                'pageTitle' => 'Products',
                'centralUser' => $centralUser,
            ])

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
        BLADE, ['centralUser' => CentralShellFixture::user()]);

        $this->assertStringContainsString('aria-current="page"', $html);
        $this->assertStringContainsString('Products shell content', $html);
        $this->assertStringContainsString('Create placeholder', $html);
        $this->assertStringContainsString('aria-label="Breadcrumbs"', $html);
    }
}

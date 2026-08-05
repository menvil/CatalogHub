<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminUiTokensTest extends TestCase
{
    public function test_admin_ui_tokens_are_defined_for_tailwind_build(): void
    {
        $colors = file_get_contents(resource_path('css/tokens/colors.css'));
        $geometry = file_get_contents(resource_path('css/tokens/geometry.css'));
        $foundation = file_get_contents(resource_path('css/foundation.css'));
        $entryPoint = file_get_contents(resource_path('css/app.css'));

        $this->assertIsString($colors);
        $this->assertIsString($geometry);
        $this->assertIsString($foundation);
        $this->assertIsString($entryPoint);
        $this->assertStringContainsString("@import './tokens/colors.css';", $foundation);
        $this->assertStringContainsString("@import './tokens/geometry.css';", $foundation);
        $this->assertStringContainsString("@import './foundation.css';", $entryPoint);
        $css = $colors.$geometry;

        foreach ([
            '--color-admin-background',
            '--color-admin-surface',
            '--color-admin-surface-muted',
            '--color-admin-border',
            '--color-admin-text',
            '--color-admin-muted',
            '--color-admin-primary',
            '--color-admin-primary-soft',
            '--color-admin-success',
            '--color-admin-success-soft',
            '--color-admin-warning',
            '--color-admin-warning-soft',
            '--color-admin-danger',
            '--color-admin-danger-soft',
            '--color-admin-info',
            '--color-admin-info-soft',
            '--color-admin-outdated',
            '--color-admin-outdated-soft',
            '--spacing-admin-page',
            '--spacing-admin-card',
            '--spacing-admin-section',
            '--spacing-admin-field',
            '--radius-admin-card',
            '--radius-admin-input',
            '--radius-admin-badge',
            '--radius-admin-modal',
            '--shadow-admin-card',
            '--shadow-admin-floating',
            '--shadow-admin-modal',
        ] as $token) {
            $this->assertStringContainsString($token, $css);
        }
    }

    public function test_dev_ui_kit_view_uses_admin_tokens(): void
    {
        $html = view('dev.ui-kit')->render();

        $this->assertStringContainsString('CatalogHub Admin UI Kit', $html);
        $this->assertStringContainsString('bg-admin-background', $html);
        $this->assertStringContainsString('rounded-admin-card', $html);
        $this->assertStringContainsString('shadow-admin-card', $html);
    }
}

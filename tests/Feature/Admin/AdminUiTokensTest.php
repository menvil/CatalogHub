<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminUiTokensTest extends TestCase
{
    public function test_admin_ui_tokens_are_defined_for_tailwind_build(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertIsString($css);

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

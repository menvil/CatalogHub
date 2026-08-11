<?php

declare(strict_types=1);

namespace Tests\Feature\SystemPages;

use Illuminate\Contracts\Foundation\MaintenanceMode;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class MaintenancePageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/__foundation-error/503', static fn (): never => abort(503, 'database unavailable', ['Retry-After' => '120']));
        Route::get('/admin/site/__foundation-error/503', static fn (): never => abort(503, 'database unavailable', ['Retry-After' => '120']));
    }

    public function test_maintenance_page_needs_no_database_or_built_asset_and_preserves_retry_after(): void
    {
        $this->get('/__foundation-error/503')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '120')
            ->assertSee('data-application-error="503"', false)
            ->assertSee('Temporarily unavailable')
            ->assertDontSee('/build/assets/', false)
            ->assertDontSee('database unavailable');
    }

    public function test_admin_maintenance_keeps_the_requested_admin_context(): void
    {
        $this->get('/admin/site/__foundation-error/503')
            ->assertStatus(503)
            ->assertHeader('Retry-After', '120')
            ->assertSee('data-admin-error="503"', false)
            ->assertSee('data-presentation-context="site-admin"', false)
            ->assertDontSee('database unavailable');
    }

    public function test_framework_maintenance_mode_uses_the_same_safe_screen(): void
    {
        $maintenance = app(MaintenanceMode::class);
        $maintenance->activate(['retry' => 90]);

        try {
            $this->get('/admin/central')
                ->assertStatus(503)
                ->assertHeader('Retry-After', '90')
                ->assertSee('data-admin-error="503"', false)
                ->assertDontSee('/build/assets/', false);
        } finally {
            $maintenance->deactivate();
        }
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\SystemPages;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AdminForbiddenPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/admin/central/__foundation-error/403', static fn (): never => abort(403, 'central.permission.secret'));
        Route::get('/admin/site/__foundation-error/403', static fn (): never => abort(403, 'site.permission.secret'));
    }

    public function test_central_and_site_denials_use_their_own_safe_admin_context(): void
    {
        $this->get('/admin/central/__foundation-error/403')
            ->assertForbidden()
            ->assertSee('data-admin-error="403"', false)
            ->assertSee('data-presentation-context="central-admin"', false)
            ->assertSee('Return to Central Admin')
            ->assertDontSee('central.permission.secret')
            ->assertDontSee('site-admin', false);

        $this->get('/admin/site/__foundation-error/403')
            ->assertForbidden()
            ->assertSee('data-admin-error="403"', false)
            ->assertSee('data-presentation-context="site-admin"', false)
            ->assertSee('Return to Site Admin')
            ->assertDontSee('site.permission.secret')
            ->assertDontSee('central-admin', false);
    }
}

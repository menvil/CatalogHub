<?php

declare(strict_types=1);

namespace Tests\Feature\SystemPages;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AdminNotFoundPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('/admin/central/__foundation-error/concealed', static fn (): never => abort(404, 'Restricted brand 42 exists'));
    }

    public function test_unknown_admin_routes_return_to_the_correct_dashboard(): void
    {
        $this->get('/admin/central/route-that-does-not-exist')
            ->assertNotFound()
            ->assertSee('data-admin-error="404"', false)
            ->assertSee('data-presentation-context="central-admin"', false)
            ->assertSee('href="/admin/central"', false);

        $this->get('/admin/site/route-that-does-not-exist')
            ->assertNotFound()
            ->assertSee('data-admin-error="404"', false)
            ->assertSee('data-presentation-context="site-admin"', false)
            ->assertSee('href="/admin/site"', false);
    }

    public function test_concealed_resource_details_are_not_rendered(): void
    {
        $this->get('/admin/central/__foundation-error/concealed')
            ->assertNotFound()
            ->assertSee('The requested page could not be found.')
            ->assertDontSee('Restricted brand 42 exists');
    }
}

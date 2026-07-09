<?php

namespace Tests\Feature\Dev;

use Tests\TestCase;

class AdminVisualSmokePageTest extends TestCase
{
    public function test_visual_smoke_page_renders_composed_admin_component_groups(): void
    {
        $this->get('/dev/admin-visual-smoke')
            ->assertOk()
            ->assertSee('Admin visual smoke')
            ->assertSee('Dashboard composition preview')
            ->assertSee('Form composition preview')
            ->assertSee('Workflow composition preview')
            ->assertSee('Table/action composition preview')
            ->assertSee('Catalog quality')
            ->assertSee('Product title')
            ->assertSee('Vendor feed')
            ->assertSee('Demo product');
    }
}

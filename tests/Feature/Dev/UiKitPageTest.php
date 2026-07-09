<?php

namespace Tests\Feature\Dev;

use Tests\TestCase;

class UiKitPageTest extends TestCase
{
    public function test_dev_ui_kit_route_is_available_in_testing_environment(): void
    {
        $this->assertTrue(app()->environment('testing'));

        $this->get('/dev/ui-kit')
            ->assertOk()
            ->assertSee('CatalogHub Admin UI Kit')
            ->assertSee('Admin design tokens')
            ->assertSee('Status badges')
            ->assertSee('Admin cards')
            ->assertSee('Drawer')
            ->assertSee('Confirmation modal')
            ->assertSee('Stepper wizard')
            ->assertSee('Diff viewer')
            ->assertSee('Localized field editor')
            ->assertSee('Unit value input')
            ->assertSee('Attribute value editor')
            ->assertSee('Media picker')
            ->assertSee('Import progress panel')
            ->assertSee('Conflict review card')
            ->assertSee('Change request card');
    }
}

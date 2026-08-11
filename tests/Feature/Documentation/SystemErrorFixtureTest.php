<?php

declare(strict_types=1);

namespace Tests\Feature\Documentation;

use Tests\TestCase;

final class SystemErrorFixtureTest extends TestCase
{
    public function test_system_error_fixture_uses_the_safe_central_error_template_with_a_fixed_request_id(): void
    {
        $this->get('/dev/system-error')
            ->assertStatus(500)
            ->assertSee('data-presentation-context="central-admin"', false)
            ->assertSee('data-admin-error="500"', false)
            ->assertSee('Something went wrong')
            ->assertSee('00000000-0000-4000-8000-000000000007')
            ->assertDontSee('database-password=secret');
    }
}

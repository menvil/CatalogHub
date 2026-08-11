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

    public function test_system_error_fixture_is_not_registered_in_production(): void
    {
        $output = [];
        $exitCode = 0;

        exec('APP_ENV=production '.PHP_BINARY.' artisan route:list --path=dev/system-error --json 2>&1', $output, $exitCode);

        $this->assertSame(0, $exitCode, implode(PHP_EOL, $output));
        $this->assertStringContainsString('doesn\'t have any routes matching', implode(PHP_EOL, $output));
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class CheckRuntimePlatformCommandTest extends TestCase
{
    #[Test]
    public function it_rejects_the_sqlite_test_database_as_a_production_runtime(): void
    {
        $this->artisan('cataloghub:platform-check')
            ->expectsOutputToContain('PHP: 8.5')
            ->expectsOutputToContain('Database driver: sqlite')
            ->expectsOutputToContain('PostgreSQL is required as the production database')
            ->assertExitCode(1);
    }
}

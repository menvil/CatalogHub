<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Platform;

use App\Services\Platform\RuntimePlatformChecker;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class RuntimePlatformCheckerTest extends TestCase
{
    #[Test]
    public function it_accepts_the_minimum_supported_runtime(): void
    {
        $result = $this->checker()->assess('8.5.0', 'pgsql', '18.4');

        self::assertTrue($result->supported);
        self::assertSame([], $result->failures);
    }

    #[Test]
    public function it_accepts_newer_supported_runtime_versions(): void
    {
        $result = $this->checker()->assess('8.6.1', 'pgsql', '19.1 (Debian 19.1-1)');

        self::assertTrue($result->supported);
        self::assertSame('19.1', $result->postgresVersion);
    }

    #[Test]
    public function it_rejects_php_older_than_85(): void
    {
        $result = $this->checker()->assess('8.4.99', 'pgsql', '18.4');

        self::assertFalse($result->supported);
        self::assertContains('PHP 8.5.0 or newer is required; detected 8.4.99.', $result->failures);
    }

    #[Test]
    public function it_rejects_postgres_older_than_184(): void
    {
        $result = $this->checker()->assess('8.5.0', 'pgsql', '18.3');

        self::assertFalse($result->supported);
        self::assertContains('PostgreSQL 18.4 or newer is required; detected 18.3.', $result->failures);
    }

    #[Test]
    public function it_rejects_a_non_postgres_primary_database(): void
    {
        $result = $this->checker()->assess('8.5.0', 'mysql', '11.4');

        self::assertFalse($result->supported);
        self::assertContains('PostgreSQL is required as the production database; detected mysql.', $result->failures);
    }

    private function checker(): RuntimePlatformChecker
    {
        return $this->app->make(RuntimePlatformChecker::class);
    }
}

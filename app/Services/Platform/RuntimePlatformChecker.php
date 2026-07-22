<?php

declare(strict_types=1);

namespace App\Services\Platform;

use Illuminate\Database\DatabaseManager;
use PDO;
use RuntimeException;
use Throwable;

final readonly class RuntimePlatformChecker
{
    public function __construct(private DatabaseManager $database) {}

    public function check(): RuntimePlatformCheckResult
    {
        try {
            $connection = $this->database->connection();
            $driver = $connection->getDriverName();
            $serverVersion = $driver === 'pgsql'
                ? $connection->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION)
                : null;
        } catch (Throwable) {
            return new RuntimePlatformCheckResult(
                supported: false,
                phpVersion: PHP_VERSION,
                databaseDriver: 'unavailable',
                postgresVersion: null,
                failures: ['The configured database connection is unavailable.'],
            );
        }

        return $this->assess(
            PHP_VERSION,
            $driver,
            is_scalar($serverVersion) ? (string) $serverVersion : null,
        );
    }

    public function assess(string $phpVersion, string $databaseDriver, ?string $serverVersion): RuntimePlatformCheckResult
    {
        $minimumPhp = $this->stringConfig('platform.minimum_php_version');
        $minimumPostgres = $this->stringConfig('platform.minimum_postgres_version');
        $postgresVersion = $this->normalizePostgresVersion($serverVersion);
        $failures = [];

        if (version_compare($phpVersion, $minimumPhp, '<')) {
            $failures[] = "PHP {$minimumPhp} or newer is required; detected {$phpVersion}.";
        }

        if ($databaseDriver !== 'pgsql') {
            $failures[] = "PostgreSQL is required as the production database; detected {$databaseDriver}.";
        } elseif ($postgresVersion === null) {
            $failures[] = 'The PostgreSQL server version could not be determined.';
        } elseif (version_compare($postgresVersion, $minimumPostgres, '<')) {
            $failures[] = "PostgreSQL {$minimumPostgres} or newer is required; detected {$postgresVersion}.";
        }

        return new RuntimePlatformCheckResult(
            supported: $failures === [],
            phpVersion: $phpVersion,
            databaseDriver: $databaseDriver,
            postgresVersion: $postgresVersion,
            failures: $failures,
        );
    }

    private function normalizePostgresVersion(?string $version): ?string
    {
        if ($version === null || preg_match('/^(\d+\.\d+)/', $version, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function stringConfig(string $key): string
    {
        $value = config($key);

        if (! is_string($value) || $value === '') {
            throw new RuntimeException("Runtime platform setting {$key} is invalid.");
        }

        return $value;
    }
}
